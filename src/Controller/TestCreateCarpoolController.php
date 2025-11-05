<?php
// src/Controller/TestCreateCarpoolController.php

namespace App\Controller;

use App\Entity\Carpool;
use App\Entity\User;
use App\Entity\Car;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestCreateCarpoolController extends AbstractController
{
    #[Route('/test/create-carpool', name: 'test_create_carpool')]
    public function create(EntityManagerInterface $em): Response
    {
        // Récupérer un utilisateur existant pour le chauffeur
        $driver = $em->getRepository(User::class)->find(1);
        if (!$driver) {
            return new Response('Chauffeur non trouvé', 404);
        }

        // Récupérer une voiture existante
        $car = $em->getRepository(Car::class)->find(4);
        if (!$car) {
            return new Response('Voiture non trouvée', 404);
        }

        $carpool = new Carpool();
        $carpool->setDriver($driver);
        $carpool->setCar($car);
        $carpool->setDepartureDate(new \DateTime('tomorrow'));
        $carpool->setDepartureTime(new \DateTime('10:00'));
        $carpool->setDepartureLocation('Paris');
        $carpool->setArrivalDate(new \DateTime('tomorrow'));
        $carpool->setArrivalTime(new \DateTime('12:00'));
        $carpool->setArrivalLocation('Lyon');
        $carpool->setPricePerSeat(20.0);
        $carpool->setTotalSeats(4);
        $carpool->setAvailableSeats(4);

        $em->persist($carpool);
        $em->flush();

        return new Response('Covoiturage créé avec ID : ' . $carpool->getId());
    }
}