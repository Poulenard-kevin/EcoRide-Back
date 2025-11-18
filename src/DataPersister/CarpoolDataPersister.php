<?php
// src/DataPersister/CarpoolDataPersister.php
namespace App\DataPersister;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use App\Entity\Car;
use App\Entity\Carpool;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;

class CarpoolDataPersister implements ContextAwareDataPersisterInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private LoggerInterface $logger
    ) {}

    public function supports($data, array $context = []): bool
    {
        return $data instanceof Carpool;
    }

    public function persist($data, array $context = [])
    {
        if (!($data instanceof Carpool)) {
            return $data;
        }

        $user = $this->security->getUser();
        $this->logger->info('CarpoolDataPersister persist', [
            'user' => $user ? $user->getId() : 'null',
            'carpoolId' => $data->getId()
        ]);

        if ($data->getDriver() === null) {
            $data->setDriver($user);
            $this->logger->info('Driver set to current user', ['driver_id' => $user?->getId()]);
        } elseif ($data->getDriver()->getId() !== $user?->getId()) {
            throw new \InvalidArgumentException('Vous ne pouvez pas créer un covoiturage pour un autre conducteur.');
        }

        $car = $data->getCar();
        if ($car) {
            // Recharger la voiture depuis la base pour garantir les relations
            $carFromDb = $this->entityManager->getRepository(Car::class)->find($car->getId());

            if (!$carFromDb) {
                $this->logger->warning('Car not found when creating carpool', [
                    'carId' => $car->getId(),
                    'currentUserId' => $user?->getId()
                ]);
                throw new NotFoundHttpException('La voiture sélectionnée est introuvable.');
            }

            $owner = $carFromDb->getOwner();
            $ownerId = $owner?->getId();

            $this->logger->info('Checking car ownership', [
                'carId' => $carFromDb->getId(),
                'ownerId' => $ownerId ?? null,
                'currentUserId' => $user?->getId()
            ]);

            if ($owner && $ownerId !== $user?->getId()) {
                $this->logger->warning('Car ownership mismatch', [
                    'carId' => $carFromDb->getId(),
                    'ownerId' => $ownerId,
                    'currentUserId' => $user?->getId()
                ]);
                throw new AccessDeniedHttpException('Vous ne pouvez pas utiliser une voiture qui ne vous appartient pas.');
            }

            if ($data->getTotalSeats() === null) {
                if (method_exists($carFromDb, 'getSeats')) {
                    $nbSeats = (int) $carFromDb->getSeats();
                    $data->setTotalSeats($nbSeats);
                    if ($data->getAvailableSeats() === null) {
                        $data->setAvailableSeats($nbSeats);
                    }
                    $this->logger->info('totalSeats mis à jour depuis la voiture', [
                        'carpoolId' => $data->getId(),
                        'carId' => $carFromDb->getId(),
                        'totalSeats' => $nbSeats
                    ]);
                }
            }
        }

        if (($data->getAvailableSeats() ?? 0) < 0) {
            throw new \InvalidArgumentException('Le nombre de places disponibles ne peut pas être négatif.');
        }

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }

    public function remove($data, array $context = [])
    {
        if (!($data instanceof Carpool)) {
            return;
        }

        $this->entityManager->remove($data);
        $this->entityManager->flush();
    }
}