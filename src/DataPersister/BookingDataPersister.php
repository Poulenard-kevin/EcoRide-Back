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
            // assign passenger if absent
            $user = $this->security->getUser();
            if (null === $data->getPassenger()) {
                if (!$user) {
                    throw new HttpException(401, 'Authentication required to create a booking.');
                }
                $data->setPassenger($user);
            }

            if (!$data->getPassenger()) {
                throw new HttpException(400, 'A passenger must be provided.');
            }

            if (!$data->getCarpool()) {
                throw new HttpException(400, 'A carpool must be provided for the booking.');
            }

            // Fallback pour récupérer le nombre de sièges (compatibilité noms différents)
            $seatGetters = ['getSeats', 'getReservedSeats', 'getNbPlaces', 'getPlaces'];
            $newSeats = null;
            foreach ($seatGetters as $m) {
                if (method_exists($data, $m)) {
                    $newSeats = (int) $data->{$m}();
                    break;
                }
            }
            if (null === $newSeats) {
                throw new HttpException(500, 'Booking entity: no seats getter found (expected getSeats or equivalent).');
            }
            if ($newSeats <= 0) {
                throw new HttpException(400, 'Invalid number of seats requested.');
            }

            $conn = $this->em->getConnection();
            $conn->beginTransaction();
            try {
                // charger et locker le carpool DANS la transaction
                $carpoolRepo = $this->em->getRepository(Carpool::class);
                $carpool = $carpoolRepo->find($data->getCarpool()->getId());
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
                $available = $carpool->getTotalSeats() - $bookedSeatsExclCurrent;

                // Ancien nombre de sièges (si mise à jour)
                $oldSeats = 0;
                if ($data->getId()) {
                    $existing = $this->em->getRepository(Booking::class)->find($data->getId());
                    if ($existing) {
                        // fallback getter pour l'existant
                        $oldSeats = (int) (
                            method_exists($existing, 'getSeats') ? $existing->getSeats()
                            : (method_exists($existing, 'getReservedSeats') ? $existing->getReservedSeats() : 0)
                        );
                    }
                }

                $delta = $newSeats - $oldSeats;
                if ($delta > 0 && $available < $delta) {
                    throw new HttpException(400, 'Not enough available seats for this carpool.');
                }

                // Sauvegarder la réservation
                $this->em->persist($data);
                $this->em->flush();

                // Recalculer et mettre à jour availableSeats (prop source de vérité)
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

                return $data;
            } catch (\Throwable $e) {
                $conn->rollBack();
                // log l'exception complète pour debugging
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

        $carpool = $data->getCarpool();
        if (!$carpool) {
            $this->em->remove($data);
            $this->em->flush();
            return;
        }

        $conn = $this->em->getConnection();
        $conn->beginTransaction();
        try {
            $carpoolRepo = $this->em->getRepository(Carpool::class);
            $carpool = $carpoolRepo->find($carpool->getId());
            if ($carpool) {
                $this->em->lock($carpool, LockMode::PESSIMISTIC_WRITE);
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
            }

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollBack();
            $this->logger->error('Booking remove failed: '.$e->getMessage(), ['exception' => $e]);
            throw new HttpException(500, 'An error occurred while deleting the booking.');
        }
    }
}