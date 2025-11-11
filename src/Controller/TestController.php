<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Car;
use App\Entity\Carpool;
use App\Entity\Review;
use App\Entity\Booking;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route('/test', name: 'app_test')]
    public function index(EntityManagerInterface $em): Response
    {
        // Création d'un utilisateur
        $user = new User();
        $user->setLastName('Dupont');
        $user->setFirstName('Jean');
        $user->setEmail('jean.dupont@example.com');
        $user->setPassword('password');
        $user->setRoles([User::ROLE_USER]);

        $em->persist($user);

        // Création d'une voiture liée à l'utilisateur
        $car = new Car();
        $car->setOwner($user);
        $car->setBrand('Toyota');
        $car->setModel('Corolla');
        $car->setColor('Blue');
        $car->setFuelType('Thermique');
        $car->setRegistration('AB-123-CD');
        $car->setSeats(5);

        $em->persist($car);

        // Création d'un covoiturage lié à l'utilisateur et à la voiture
        $carpool = new Carpool();
        $carpool->setDriver($user);
        $carpool->setCar($car);
        $carpool->setDepartureDate(new \DateTime('tomorrow'));
        $carpool->setDepartureTime(new \DateTime('tomorrow 08:00'));
        $carpool->setDepartureLocation('Paris');
        $carpool->setArrivalDate(new \DateTime('tomorrow'));
        $carpool->setArrivalTime(new \DateTime('tomorrow 12:00'));
        $carpool->setArrivalLocation('Lyon');
        $carpool->setPricePerSeat(20.0);
        $carpool->setTotalSeats(4);
        $carpool->setAvailableSeats(4);
        $carpool->setStatus(Carpool::STATUS_ACTIVE);

        $em->persist($carpool);

        // Création d'un avis lié à l'utilisateur et au covoiturage
        $review = new Review();
        $review->setAuthor($user);
        $review->setCarpool($carpool);
        $review->setTarget($user);
        $review->setRating(5);
        $review->setComment('Super trajet !');
        $review->setDate(new \DateTime());

        $em->persist($review);

        // Création d'une réservation liée à l'utilisateur et au covoiturage
        $booking = new Booking();
        $booking->setPassenger($user);
        $booking->setCarpool($carpool);
        $booking->setBookingDate(new \DateTime());
        $booking->setReservedSeats(1);
        $booking->setStatus(Booking::STATUS_CONFIRMED);

        $em->persist($booking);

        // Enregistrer en base
        $em->flush();

        // Récupérer les labels français des statuts
        $statusCarpool = $carpool->getStatusLabel();
        $statusBooking = $booking->getStatusLabel();

        $responseContent = "Les entités de test ont été créées avec succès !<br>";
        $responseContent .= "Statut du covoiturage : " . $statusCarpool . "<br>";
        $responseContent .= "Statut de la réservation : " . $statusBooking;

        return new Response($responseContent);
    }
}