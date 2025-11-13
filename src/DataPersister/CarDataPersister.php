<?php
namespace App\DataPersister;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use App\Entity\Car;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class CarDataPersister implements ContextAwareDataPersisterInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private RequestStack $requestStack,
        private LoggerInterface $logger
    ) {}

    public function supports($data, array $context = []): bool
    {
        return $data instanceof Car;
    }

    public function persist($data, array $context = [])
    {
        $method = $this->requestStack->getCurrentRequest()?->getMethod();
        $this->logger->info('CarDataPersister.persist called', [
            'method' => $method,
            'operation' => $context['collection_operation_name'] ?? $context['item_operation_name'] ?? null,
        ]);

        $user = $this->security->getUser();
        $this->logger->info('Current user in CarDataPersister', [
            'user' => $user ? $user->getId() . ' - ' . $user->getEmail() : 'null'
        ]);

        if ($method === 'POST') {
            if ($user && null === $data->getOwner()) {
                $data->setOwner($user);
                $this->logger->info('Owner set to current user', ['owner_id' => $user->getId()]);
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