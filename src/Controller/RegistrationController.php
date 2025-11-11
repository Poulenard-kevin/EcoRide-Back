<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();

        // Utilise $this->createForm() au lieu de FormFactoryInterface
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer le mot de passe en clair
            $plainPassword = $form->get('plainPassword')->getData();

            // Hacher le mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            // Le rôle est déjà défini dans __construct de User, mais tu peux forcer ici
            // $user->setRoless(User::ROLE_USER);

            // Sauvegarder en base
            $em->persist($user);
            $em->flush();

            // Message flash + redirection vers login
            $this->addFlash('success', 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /*#[Route('/create-users', name: 'create_users')]
    public function createUsers(
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        // Vérifie si des utilisateurs existent déjà
        $existingUsers = $em->getRepository(User::class)->findAll();
        
        if (count($existingUsers) > 0) {
            return new Response('Des utilisateurs existent déjà ! Videz la base si vous voulez recréer.');
        }

        // ✅ 1. Créer un ADMIN
        $admin = new User();
        $admin->setFirstName('Admin');
        $admin->setLastName('EcoRide');
        $admin->setEmail('admin@ecoride.com');
        $admin->setRoles(User::ROLE_ADMIN);
        $admin->setPassword($passwordHasher->hashPassword($admin, 'Admin1234!'));
        $em->persist($admin);

        // ✅ 2. Créer un EMPLOYÉ
        $employee = new User();
        $employee->setFirstName('Employé');
        $employee->setLastName('Support');
        $employee->setEmail('employe@ecoride.com');
        $employee->setRoles(User::ROLE_EMPLOYE);
        $employee->setPassword($passwordHasher->hashPassword($employee, 'Employe1234!'));
        $em->persist($employee);

        // ✅ 3. Créer un USER
        $user = new User();
        $user->setFirstName('Kevin');
        $user->setLastName('Poulenard');
        $user->setEmail('kevin@ecoride.com');
        $user->setRoles(User::ROLE_USER);
        $user->setPassword($passwordHasher->hashPassword($user, 'User1234!'));
        $em->persist($user);

        // ✅ 4. Créer un deuxième USER (pour tester les réservations)
        $user2 = new User();
        $user2->setFirstName('Marie');
        $user2->setLastName('Dupont');
        $user2->setEmail('marie@ecoride.com');
        $user2->setRoles(User::ROLE_USER);
        $user2->setPassword($passwordHasher->hashPassword($user2, 'User1234!'));
        $em->persist($user2);

        $em->flush();
        
        return new Response('
            <h1>✅ Utilisateurs créés avec succès !</h1>
            <table border="1" cellpadding="10" style="border-collapse: collapse;">
                <thead>
                    <tr>
                        <th>Rôle</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Mot de passe</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>ADMIN</strong></td>
                        <td>Admin EcoRide</td>
                        <td>admin@ecoride.com</td>
                        <td>Admin1234!</td>
                    </tr>
                    <tr>
                        <td><strong>EMPLOYÉ</strong></td>
                        <td>Employé Support</td>
                        <td>employe@ecoride.com</td>
                        <td>Employe1234!</td>
                    </tr>
                    <tr>
                        <td><strong>USER</strong></td>
                        <td>Kevin Poulenard</td>
                        <td>kevin@ecoride.com</td>
                        <td>User1234!</td>
                    </tr>
                    <tr>
                        <td><strong>USER</strong></td>
                        <td>Marie Dupont</td>
                        <td>marie@ecoride.com</td>
                        <td>User1234!</td>
                    </tr>
                </tbody>
            </table>
            <br>
            <a href="/login" style="padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">Se connecter</a>
        ');
    }*/
}