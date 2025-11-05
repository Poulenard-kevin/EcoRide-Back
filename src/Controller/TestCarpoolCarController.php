<?php

namespace App\Controller;

use App\Entity\Carpool;
use App\Entity\Car;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestCarpoolCarController extends AbstractController
{
    #[Route('/test/carpool-car', name: 'test_carpool_car')]
    public function test(EntityManagerInterface $em): Response
    {
        $car = $em->getRepository(Car::class)->find(1);
        if (!$car) {
            return new Response('Voiture non trouvée', 404);
        }

        $carpool = new Carpool();
        $carpool->setDepartureDate(new \DateTime('tomorrow'));
        $carpool->setDepartureTime(new \DateTime('10:00'));
        $carpool->setArrivalDate(new \DateTime('tomorrow'));
        $carpool->setArrivalTime(new \DateTime('12:00'));
        $carpool->setDepartureLocation('Paris');
        $carpool->setArrivalLocation('Lyon');
        $carpool->setPricePerSeat(20.0);
        $carpool->setTotalSeats(4);
        $carpool->setAvailableSeats(4);
        $carpool->setDriver($car->getOwner());
        $carpool->setCar($car);

        $em->persist($carpool);
        $em->flush();

        return new Response('Covoiturage créé et lié à la voiture avec ID : ' . $carpool->getId());
    }
}