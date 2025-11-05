<?php

namespace App\Controller;

use App\Entity\User; // Renommage de l'entité Utilisateur en User
use App\Entity\Car; // Renommage de l'entité Voiture en Car
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestUserCarController extends AbstractController // Renommage du contrôleur
{
    #[Route('/test/user-car', name: 'test_user_car')] // Mise à jour de la route
    public function test(EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find(1); // Utilisation de User
        if (!$user) {
            return new Response('Utilisateur non trouvé', 404); // Message en français
        }

        $car = new Car(); // Utilisation de Car
        $car->setBrand('Toyota'); // Renommage des méthodes
        $car->setModel('Corolla');
        $car->setColor('Bleu');
        $car->setFuelType('Essence');
        $car->setRegistration('AB-123-CD');
        $car->setSeats(5);
        $car->setOwner($user); // Renommage de la méthode

        $em->persist($car);
        $em->flush();

        return new Response('Voiture créée et liée à l\'utilisateur avec l\'ID : ' . $car->getId()); // Message en français
    }
}