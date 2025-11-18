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

            // 4. Récupérer le nombre de sièges réservés
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
                // Charger et locker le carpool
                $carpoolRepo = $this->em->getRepository(Carpool::class);
                $carpool = $carpoolRepo->find($carpool->getId());
                if (!$carpool) {
                    throw new HttpException(404, 'Carpool not found.');
                }
                $this->em->lock($carpool, LockMode::PESSIMISTIC_WRITE);

                // Calculer les sièges réservés (hors la réservation actuelle si mise à jour)
                $qb = $this->em->createQueryBuilder()
                    ->select('COALESCE(SUM(b.reservedSeats), 0)')
                    ->from(Booking::class, 'b')
                    ->where('b.carpool = :c')
                    ->setParameter('c', $carpool);

                if ($data->getId()) {
                    $qb->andWhere('b.id != :bid')->setParameter('bid', $data->getId());
                }

                $bookedSeatsExclCurrent = (int) $qb->getQuery()->getSingleScalarResult();
                $available = $carpool->getAvailableSeats() ?? 0;

                // Ancien nombre de sièges (si mise à jour)
                $oldSeats = 0;
                if ($data->getId()) {
                    $existing = $this->em->getRepository(Booking::class)->find($data->getId());
                    if ($existing) {
                        $oldSeats = (int) (
                            method_exists($existing, 'getReservedSeats') ? $existing->getReservedSeats() : 0
                        );
                    }
                }

                $delta = $newSeats - $oldSeats;
                if ($delta > 0 && $available < $delta) {
                    throw new HttpException(400, 'Pas assez de places disponibles pour cette réservation.');
                }

                // Sauvegarder la réservation
                $this->em->persist($data);
                $this->em->flush();

                // Recalculer et mettre à jour availableSeats
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

                $conn->commit();

                $this->logger->info('Booking created/updated', [
                    'bookingId' => $data->getId(),
                    'passenger' => $passenger->getId(),
                    'carpoolId' => $carpool->getId(),
                    'reservedSeats' => $newSeats,
                    'availableSeats' => $carpool->getAvailableSeats()
                ]);

                return $data;
            } catch (\Throwable $e) {
                $conn->rollBack();
                $this->logger->error('Booking persist failed: '.$e->getMessage(), ['exception' => $e]);
                throw new HttpException(500, 'An error occurred while saving the booking.');
            }
        }

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

            if ($carpool) {
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
            $conn->rollBack();
            $this->logger->error('Booking remove failed: '.$e->getMessage(), ['exception' => $e]);
            throw new HttpException(500, 'An error occurred while deleting the booking.');
        }
    }
}