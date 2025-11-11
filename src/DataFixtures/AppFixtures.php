<?php
// src/DataFixtures/AppFixtures.php
namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Création d'un utilisateur admin
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setLastName('Admin');
        $admin->setFirstName('Super');
        $admin->setRoles(User::ROLE_ADMIN);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'AdminPass123!'));
        $manager->persist($admin);

        // Création d'un utilisateur employé
        $employe = new User();
        $employe->setEmail('employe@example.com');
        $employe->setLastName('Employe');
        $employe->setFirstName('Jean');
        $employe->setRoles(User::ROLE_EMPLOYE);
        $employe->setPassword($this->passwordHasher->hashPassword($employe, 'EmployePass123!'));
        $manager->persist($employe);

        $manager->flush();
    }
}