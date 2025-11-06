<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TestValidationController extends AbstractController
{
    #[Route('/test-validation', name: 'app_test_validation')]
    public function index(
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = new User();
        $user->setLastName($request->query->get('lastName', ''));
        $user->setFirstName($request->query->get('firstName', ''));
        $user->setEmail($request->query->get('email', ''));

        $plainPassword = $request->query->get('password', '');

        $errors = $validator->validate($user);

        if (count($errors) > 0) {
            $errorsString = (string) $errors;
            return new Response("Erreurs de validation :<br>" . nl2br($errorsString));
        }

        // Hashage du mot de passe
        $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        // Persister en base
        $em->persist($user);
        $em->flush();

        return new Response('Utilisateur valide et enregistré en base !');
    }
}