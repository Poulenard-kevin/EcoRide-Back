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
            $this->em->remove($data);
            $this->em->flush();
            return;
        }

        $conn = $this->em->getConnection();
        $conn->beginTransaction();
        try {
            $carpool = $data->getCarpool();
            if ($carpool) {
                $carpoolRepo = $this->em->getRepository(Carpool::class);
                $carpool = $carpoolRepo->find($carpool->getId());
                if ($carpool) {
                    $this->em->lock($carpool, LockMode::PESSIMISTIC_WRITE);
                }
            }

            $this->em->remove($data);
            $this->em->flush();

            if ($carpool && method_exists($carpool, 'setAvailableSeats') && method_exists($carpool, 'getTotalSeats')) {
                $totalBooked = (int) $this->em->createQueryBuilder()
                    ->select('COALESCE(SUM(b.reservedSeats), 0)')
                    ->from(Booking::class, 'b')
                    ->where('b.carpool = :c')
                    ->setParameter('c', $carpool)
                    ->getQuery()
                    ->getSingleScalarResult();

                $carpool->setAvailableSeats($carpool->getTotalSeats() - $totalBooked);
                $this->em->persist($carpool);
                $this->em->flush();

                $this->logger->info('Booking removed, availableSeats updated', [
                    'bookingId' => $data->getId(),
                    'carpoolId' => $carpool->getId(),
                    'availableSeats' => $carpool->getAvailableSeats()
                ]);
            }

            $conn->commit();
        } catch (\Throwable $e) {
            try {
                $conn->rollBack();
            } catch (\Throwable $rb) {
                $this->logger->error('Failed to roll back transaction (remove): ' . $rb->getMessage(), ['exception' => $rb]);
            }
            $this->logger->error('Booking remove failed: '.$e->getMessage(), ['exception' => $e]);

            if ($e instanceof HttpExceptionInterface) {
                throw $e;
            }

            throw new HttpException(500, 'An error occurred while deleting the booking.', $e);
        }
    }
}