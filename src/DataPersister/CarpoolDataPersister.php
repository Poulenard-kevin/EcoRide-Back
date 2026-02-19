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
        if (!$user) {
            $this->logger->error('Attempt to persist carpool without authenticated user', [
                'carpoolId' => $data->getId()
            ]);
            throw new AccessDeniedHttpException('Vous devez être connecté.');
        }

        $this->logger->info('CarpoolDataPersister persist start', [
            'userId' => method_exists($user, 'getId') ? $user->getId() : null,
            'carpoolId' => $data->getId()
        ]);

        // Ensure driver is current user or matches it
        if ($data->getDriver() === null) {
            $data->setDriver($user);
            $this->logger->info('Driver set to current user', ['driver_id' => $user->getId()]);
        } elseif ((int)$data->getDriver()->getId() !== (int)$user->getId()) {
            $this->logger->warning('Driver mismatch', [
                'driverId' => $data->getDriver()->getId(),
                'currentUserId' => $user->getId()
            ]);
            throw new \InvalidArgumentException('Vous ne pouvez pas créer un covoiturage pour un autre conducteur.');
        }

        // Resolve car: accept Car object, numeric id, or IRI string (/api/cars/123)
        $car = $data->getCar();
        $carId = null;
        $carFromDb = null;

        if ($car instanceof Car) {
            // If the Car instance is already managed, fine; otherwise we'll fetch managed entity below
            $carFromDb = $car;
            $carId = $carFromDb->getId();
        } elseif (is_int($car) || (is_string($car) && ctype_digit((string)$car))) {
            $carId = (int)$car;
        } elseif (is_string($car)) {
            // try to extract numeric id from IRI or string like "/api/cars/12"
            if (preg_match('/\/(\d+)$/', $car, $m)) {
                $carId = (int)$m[1];
            }
        }

        if ($carId !== null && $carFromDb === null) {
            $carFromDb = $this->entityManager->getRepository(Car::class)->find($carId);
        }

        if ($carId !== null && !$carFromDb) {
            $this->logger->warning('Car not found when creating carpool', [
                'carId' => $carId,
                'currentUserId' => $user->getId()
            ]);
            throw new NotFoundHttpException('La voiture sélectionnée est introuvable.');
        }

        if ($carFromDb !== null) {
            // Determine owner getter dynamically (getOwner or getProprietaire)
            $owner = null;
            if (method_exists($carFromDb, 'getOwner')) {
                $owner = $carFromDb->getOwner();
            } elseif (method_exists($carFromDb, 'getProprietaire')) {
                $owner = $carFromDb->getProprietaire();
            } else {
                $this->logger->error('Car entity has no owner getter', ['carClass' => get_class($carFromDb)]);
                throw new \RuntimeException('Internal server error: owner getter missing on Car entity.');
            }

            $ownerId = $owner ? (int)$owner->getId() : null;
            $currentUserId = (int)$user->getId();

            $this->logger->info('Owner/user ids for ownership check', [
                'carId' => $carFromDb->getId(),
                'ownerId' => $ownerId,
                'currentUserId' => $currentUserId,
            ]);

            if ($ownerId !== $currentUserId) {
                $this->logger->warning('Car ownership mismatch', [
                    'carId' => $carFromDb->getId(),
                    'ownerId' => $ownerId,
                    'currentUserId' => $currentUserId
                ]);
                throw new AccessDeniedHttpException('Vous ne pouvez pas utiliser une voiture qui ne vous appartient pas.');
            }

            // Ensure the Carpool entity holds the managed Car entity from Doctrine
            $data->setCar($carFromDb);

            // ✅ On force TOUJOURS le total depuis la voiture (source de vérité)
            $isNew = $data->getId() === null;
            $nbSeats = (int)$carFromDb->getSeats();
            $data->setTotalSeats($nbSeats);

            if ($isNew) {
                // Seulement à la création, on initialise les places dispo au max
                $data->setAvailableSeats($nbSeats);
            }

            $this->logger->info('totalSeats forcé depuis la voiture', [
                'carpoolId'  => $data->getId(),
                'carId'      => $carFromDb->getId(),
                'totalSeats' => $nbSeats,
                'isNew'      => $isNew
            ]);
        } else {
            $this->logger->info('No car provided in payload', [
                'carpoolId' => $data->getId(),
                'currentUserId' => $user->getId()
            ]);
        }

        // Validate available seats
        if (($data->getAvailableSeats() ?? 0) < 0) {
            throw new \InvalidArgumentException('Le nombre de places disponibles ne peut pas être négatif.');
        }

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        $this->logger->info('Carpool persisted', [
            'carpoolId' => $data->getId(),
            'serverId' => $data->getId()
        ]);

        return $data;
    }

    public function remove($data, array $context = [])
    {
        if (!($data instanceof Carpool)) {
            return;
        }

        $id = $data->getId();
        $this->logger->info('Tentative de suppression du Carpool', ['id' => $id]);

        try {
            // Sécurité : Si l'entité est détachée, on la récupère pour que Doctrine la reconnaisse
            if (!$this->entityManager->contains($data)) {
                $data = $this->entityManager->getRepository(Carpool::class)->find($id);
            }

            if ($data) {
                $this->entityManager->remove($data);
                $this->entityManager->flush(); // Le cascade={"remove"} va supprimer les bookings ici
                $this->logger->info('Carpool et ses réservations supprimés avec succès', ['id' => $id]);
            }
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la suppression du Carpool', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}