<?php
// src/DataPersister/BookingDataPersister.php
namespace App\DataPersister;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use App\Entity\Booking;
use App\Entity\Carpool;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\LockMode;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class BookingDataPersister implements ContextAwareDataPersisterInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private RequestStack $requestStack,
        private LoggerInterface $logger
    ) {}

    public function supports($data, array $context = []): bool
    {
        return $data instanceof Booking;
    }

    public function persist($data, array $context = [])
    {
        $request = $this->requestStack->getCurrentRequest();
        $method = $request?->getMethod();
        $operation = $context['collection_operation_name'] ?? $context['item_operation_name'] ?? null;

        // Intervenir principalement sur création / mise à jour
        if (in_array($method, ['POST', 'PUT', 'PATCH'], true) || in_array($operation, ['post','put','patch'], true)) {
            $user = $this->security->getUser();

            // 1. Assigner le passager si non défini
            if (null === $data->getPassenger()) {
                if (!$user) {
                    throw new HttpException(401, 'Authentication required to create a booking.');
                }
                $data->setPassenger($user);
            }

            // 2. Vérifier que le passager est bien l'utilisateur courant (sauf admin)
            $passenger = $data->getPassenger();
            if ($passenger->getId() !== $user?->getId() && !in_array('ROLE_ADMIN', $user->getRoles() ?? [])) {
                throw new HttpException(403, 'Vous ne pouvez pas réserver pour un autre passager.');
            }

            // 3. Vérifier que le passager n'est pas le conducteur
            $carpool = $data->getCarpool();
            if (!$carpool) {
                throw new HttpException(400, 'A carpool must be provided for the booking.');
            }

            if ($carpool->getDriver() && $passenger->getId() === $carpool->getDriver()->getId()) {
                throw new HttpException(400, 'Le conducteur ne peut pas réserver une place dans son propre covoiturage.');
            }

            // 4. Récupérer le nombre de sièges réservés depuis l'entité Booking
            $seatGetters = ['getReservedSeats', 'getSeats', 'getNbPlaces', 'getPlaces'];
            $newSeats = null;
            foreach ($seatGetters as $m) {
                if (method_exists($data, $m)) {
                    $newSeats = (int) $data->{$m}();
                    break;
                }
            }
            if (null === $newSeats) {
                throw new HttpException(500, 'Booking entity: no seats getter found (expected getReservedSeats or equivalent).');
            }
            if ($newSeats <= 0) {
                throw new HttpException(400, 'Le nombre de places réservées doit être positif.');
            }

            // 5. Calculer le prix total
            $pricePerSeat = (float) $carpool->getPricePerSeat();
            $totalPrice = $pricePerSeat * $newSeats;

            // Remplace 'setTotalPrice' par le nom exact de ton setter (ex: setAmount)
            if (method_exists($data, 'setTotalPrice')) {
                $data->setTotalPrice($totalPrice);
            }

            $this->logger->info('Calcul du prix total de la réservation', [
                'pricePerSeat' => $pricePerSeat,
                'nbPlaces' => $newSeats,
                'totalPrice' => $totalPrice
            ]);

            $conn = $this->em->getConnection();
            $conn->beginTransaction();
            try {
                // Recharger et locker le carpool pour éviter races
                $carpoolRepo = $this->em->getRepository(Carpool::class);
                $carpool = $carpoolRepo->find($carpool->getId());
                if (!$carpool) {
                    throw new HttpException(404, 'Carpool not found.');
                }
                $this->em->lock($carpool, LockMode::PESSIMISTIC_WRITE);

                // Déterminer la capacité totale du carpool
                $capacity = (int) (
                    method_exists($carpool, 'getTotalSeats') ? $carpool->getTotalSeats() :
                    (method_exists($carpool, 'getPlaces') ? $carpool->getPlaces() :
                        ($carpool->getAvailableSeats() ?? 0))
                );

                // Calculer la somme des réservations existantes (excluant l'item courant si update)
                $qb = $this->em->createQueryBuilder()
                    ->select('COALESCE(SUM(b.reservedSeats), 0)')
                    ->from(Booking::class, 'b')
                    ->where('b.carpool = :c')
                    ->setParameter('c', $carpool);

                if ($data->getId()) {
                    $qb->andWhere('b.id != :bid')->setParameter('bid', $data->getId());
                }

                $bookedSeatsExclCurrent = (int) $qb->getQuery()->getSingleScalarResult();

                // Ancien nombre de sièges en cas de mise à jour (pour calcul delta)
                $oldSeats = 0;
                if ($data->getId()) {
                    $existing = $this->em->getRepository(Booking::class)->find($data->getId());
                    if ($existing) {
                        $oldSeats = (int) (method_exists($existing, 'getReservedSeats') ? $existing->getReservedSeats() : 0);
                    }
                }

                $delta = $newSeats - $oldSeats;
                $available = $capacity - $bookedSeatsExclCurrent;

                if ($delta > 0 && $available < $delta) {
                    // Erreur métier -> 422 Unprocessable Entity
                    throw new HttpException(422, 'Le nombre de places demandées dépasse les places disponibles.');
                }

                // --- DÉBIT / CRÉDIT (Commission FIXE de 2 crédits par réservation) ---
                if ($passenger->getCredits() < $totalPrice) {
                    throw new HttpException(422, 'Crédits insuffisants pour effectuer cette réservation.');
                }

                // Commission FIXE EcoRide par réservation (indépendant du nombre de sièges)
                $commissionFixe = 2;

                // Débiter le passager du PRIX TOTAL (ex: 2 places à 5 = 10 crédits)
                $passenger->setCredits($passenger->getCredits() - (int) $totalPrice);
                $this->em->persist($passenger);

                // Créditer le chauffeur : Montant Total - 2 crédits fixes
                $driver = $carpool->getDriver();
                if ($driver && $driver->getId() !== $passenger->getId()) {
                    // Le chauffeur reçoit le total payé par le passager MOINS les 2 crédits de commission
                    $driverAmount = (int) $totalPrice - $commissionFixe;
                    
                    // Sécurité : si le prix du trajet était < 2 (peu probable), on ne débite pas le chauffeur
                    if ($driverAmount < 0) $driverAmount = 0;

                    $driver->setCredits($driver->getCredits() + $driverAmount);
                    $this->em->persist($driver);
                }

                // Optionnel : créditer un compte "plateforme" (EcoRide) pour accumuler les commissions.
                // Si tu veux enregistrer les commissions en BDD, décommente et ajuste la recherche du compte plateforme.
                // Exemple courant : compte admin id = 1 ou un utilisateur spécifique avec email "ecoride@..."
                // try {
                //     $platformUser = $this->em->getRepository(User::class)->findOneBy(['email' => 'ecoride@domain.tld']);
                //     if ($platformUser) {
                //         $platformUser->setCredits($platformUser->getCredits() + $commissionTotal);
                //         $this->em->persist($platformUser);
                //     }
                // } catch (\Throwable $e) {
                //     $this->logger->warning('Impossible de créditer le compte plateforme pour la commission', ['error' => $e->getMessage()]);
                // }

                $this->logger->info('Transaction crédits (Commission Fixe 2) préparée', [
                    'totalPrice' => $totalPrice,
                    'commission' => $commissionFixe,
                    'driverNet' => $driverAmount ?? null
                ]);
                // --- FIN DÉBIT / CRÉDIT ---

                // Persister la réservation
                $this->em->persist($data);
                $this->em->flush();

                // Recalculer le total et mettre à jour availableSeats si l'entité le supporte
                $totalBooked = (int) $this->em->createQueryBuilder()
                    ->select('COALESCE(SUM(b.reservedSeats), 0)')
                    ->from(Booking::class, 'b')
                    ->where('b.carpool = :c')
                    ->setParameter('c', $carpool)
                    ->getQuery()
                    ->getSingleScalarResult();

                if (method_exists($carpool, 'setAvailableSeats') && method_exists($carpool, 'getTotalSeats')) {
                    $carpool->setAvailableSeats($carpool->getTotalSeats() - $totalBooked);
                    $this->em->persist($carpool);
                    $this->em->flush();
                }

                $conn->commit();

                $this->logger->info('Booking created/updated', [
                    'bookingId' => $data->getId(),
                    'passenger' => $passenger->getId(),
                    'carpoolId' => $carpool->getId(),
                    'reservedSeats' => $newSeats,
                    'availableSeats' => method_exists($carpool, 'getAvailableSeats') ? $carpool->getAvailableSeats() : null
                ]);

                return $data;
            } catch (\Throwable $e) {
                // rollback obligatoire
                try {
                    $conn->rollBack();
                } catch (\Throwable $rb) {
                    $this->logger->error('Failed to roll back transaction: ' . $rb->getMessage(), ['exception' => $rb]);
                }

                $this->logger->error('Booking persist failed: '.$e->getMessage(), ['exception' => $e]);

                // Si c'est déjà une HttpException (erreur 4xx métier), on la ré-lance telle quelle pour conserver le statut
                if ($e instanceof HttpExceptionInterface) {
                    throw $e;
                }

                // Erreur inattendue -> 500 (garder l'exception d'origine en "previous")
                throw new HttpException(500, 'An error occurred while saving the booking.', $e);
            }
        }

        // Pour autres cas (delete géré ailleurs), comportement par défaut
        $this->em->persist($data);
        $this->em->flush();
        return $data;
    }

    public function remove($data, array $context = [])
    {
        if (!$data instanceof Booking) {
            return;
        }

        $id = $data->getId();
        $carpool = $data->getCarpool();
        $passenger = $data->getPassenger();

        $this->logger->info('Début suppression Booking avec remboursement', ['id' => $id]);

        $conn = $this->em->getConnection();
        $conn->beginTransaction();

        try {
            // 1. S'assurer que l'entité est gérée par l'EM
            if (!$this->em->contains($data)) {
                $data = $this->em->getRepository(Booking::class)->find($id);
            }

            if ($data) {
                // --- LOGIQUE DE REMBOURSEMENT ---
                
                // Calcul du montant à rembourser (Prix total payé par le passager)
                // On récupère le nombre de places de cette réservation précise
                $seats = (int) (method_exists($data, 'getReservedSeats') ? $data->getReservedSeats() : 1);
                $pricePerSeat = (float) $carpool->getPricePerSeat();
                $totalPrice = (int) ($pricePerSeat * $seats);
                
                // Commission fixe de 2 crédits (celle qui a été déduite à l'aller)
                $commissionFixe = 2;
                $driverAmountReceived = $totalPrice - $commissionFixe;

                // A. Rembourser le passager (Il récupère TOUT ce qu'il a payé)
                $passenger->setCredits($passenger->getCredits() + $totalPrice);
                $this->em->persist($passenger);

                // B. Reprendre les crédits au chauffeur (On lui retire ce qu'il a REÇU net)
                $driver = $carpool->getDriver();
                if ($driver && $driver->getId() !== $passenger->getId()) {
                    $driver->setCredits($driver->getCredits() - $driverAmountReceived);
                    $this->em->persist($driver);
                }

                $this->logger->info('Remboursement effectué', [
                    'passengerRefund' => $totalPrice,
                    'driverDebit' => $driverAmountReceived
                ]);

                // --- FIN LOGIQUE DE REMBOURSEMENT ---

                // Suppression effective de la réservation
                $this->em->remove($data);
                $this->em->flush();
                
                // Mise à jour des places disponibles dans le trajet
                if ($carpool && $this->em->contains($carpool)) {
                    $totalBooked = (int) $this->em->createQueryBuilder()
                        ->select('COALESCE(SUM(b.reservedSeats), 0)')
                        ->from(Booking::class, 'b')
                        ->where('b.carpool = :c')
                        ->setParameter('c', $carpool)
                        ->getQuery()
                        ->getSingleScalarResult();

                    if (method_exists($carpool, 'setAvailableSeats') && method_exists($carpool, 'getTotalSeats')) {
                        $carpool->setAvailableSeats($carpool->getTotalSeats() - $totalBooked);
                        $this->em->persist($carpool);
                        $this->em->flush();
                    }
                }
                
                $conn->commit();
                $this->logger->info('Booking supprimé et crédits restaurés', ['id' => $id]);
            }
        } catch (\Throwable $e) {
            if ($conn->isTransactionActive()) {
                $conn->rollBack();
            }
            $this->logger->error('Échec annulation Booking', ['id' => $id, 'error' => $e->getMessage()]);
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(500, 'Erreur lors de l\'annulation.', $e);
        }
    }
}