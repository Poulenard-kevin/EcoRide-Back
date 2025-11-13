<?php
namespace App\DataPersister;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserDataPersister implements ContextAwareDataPersisterInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function supports($data, array $context = []): bool
    {
        return $data instanceof User;
    }

    public function persist($data, array $context = [])
    {
        // Si un plainPassword a été fourni -> le hacher et le setter
        if ($data->getPlainPassword()) {
            $hashed = $this->passwordHasher->hashPassword($data, $data->getPlainPassword());
            $data->setPassword($hashed);
            $data->eraseCredentials();
        }

        // Valeurs par défaut si nécessaires
        if (empty($data->getRoles())) {
            $data->setRoles(['ROLE_USER']);
        }

        if (empty($data->getApiToken())) {
            $data->setApiToken(bin2hex(random_bytes(32)));
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