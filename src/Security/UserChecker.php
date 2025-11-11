<?php
namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isActive()) {
            // message affiché à l'utilisateur sans détails internes
            throw new CustomUserMessageAccountStatusException('Votre compte est suspendu.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // nothing
    }
}