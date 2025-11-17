<?php
// src/DataPersister/CarpoolDataPersister.php
namespace App\DataPersister;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use App\Entity\Carpool;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CarpoolDataPersister implements ContextAwareDataPersisterInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private RequestStack $requestStack,
        private LoggerInterface $logger
    ) {}

    public function supports($data, array $context = []): bool
    {
        return $data instanceof Carpool;
    }

    public function persist($data, array $context = [])
    {
        $request = $this->requestStack->getCurrentRequest();
        $method = $request?->getMethod();
        $operation = $context['collection_operation_name'] ?? $context['item_operation_name'] ?? null;

        // Log d'entrée et infos utiles
        $this->logger->info('CarpoolDataPersister called', ['method' => $method, 'operation' => $operation]);
        $this->logger->info('CarpoolDataPersister request debug', [
            'requestUri' => $request?->getRequestUri(),
            'cookieHeader' => $request?->headers->get('cookie'),
            'authHeader' => $request?->headers->get('authorization'),
        ]);

        // Appliquer uniquement à la création (POST)
        if (($method === 'POST') || ($operation === 'post')) {
            // Vérifier la présence de la voiture
            $car = $data->getCar();
            if (!$car) {
                throw new HttpException(400, 'A car is required to create a carpool.');
            }

            // Récupération robuste du nombre de sièges depuis l'entité Car
            $seats = null;
            if (method_exists($car, 'getSeats')) {
                $seats = (int) $car->getSeats();
            } elseif (method_exists($car, 'getNbPlaces')) {
                $seats = (int) $car->getNbPlaces();
            } elseif (method_exists($car, 'getTotalSeats')) {
                $seats = (int) $car->getTotalSeats();
            } elseif (method_exists($car, 'getNumberOfSeats')) {
                $seats = (int) $car->getNumberOfSeats();
            }

            if (null === $seats || $seats <= 0) {
                throw new HttpException(400, 'The selected car must define a positive number of seats.');
            }

            // Récupérer l'utilisateur courant
            $user = $this->security->getUser();

            // Refuser la création si aucun utilisateur n'est authentifié
            if (!$user) {
                $this->logger->error('Attempt to create carpool without authenticated user', [
                    'requestUri' => $request?->getRequestUri(),
                    'payload' => $request?->getContent()
                ]);
                throw new HttpException(401, 'Authentication required to create a carpool.');
            }

            // Sécurité : s'assurer que la voiture appartient à l'utilisateur courant
            if (method_exists($car, 'getOwner') && $car->getOwner() && $car->getOwner()->getId() !== $user->getId()) {
                throw new HttpException(403, 'You cannot create a carpool using a car that does not belong to you.');
            }

            // Affecter totalSeats depuis la voiture
            $data->setTotalSeats($seats);

            // Initialiser availableSeats = totalSeats si non fourni
            if (null === $data->getAvailableSeats()) {
                $data->setAvailableSeats($seats);
            }

            // Définir le driver si pas encore fait
            if (method_exists($data, 'getDriver') && method_exists($data, 'setDriver') && null === $data->getDriver()) {
                $data->setDriver($user);
            }
        }

        $this->em->persist($data);
        $this->em->flush();

        return $data;
    }

    public function remove($data, array $context = [])
    {
        $this->em->remove($data);
        $this->em->flush();
    }
}