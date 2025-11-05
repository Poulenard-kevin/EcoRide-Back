<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\User;
use App\Entity\Carpool;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestBookingCreationController extends AbstractController
{
    #[Route('/test/create-booking', name: 'test_create_booking')]
    public function createBooking(EntityManagerInterface $em): Response
    {
        // Récupérer un utilisateur existant (id = 1)
        $user = $em->getRepository(User::class)->find(1);
        if (!$user) {
            return new Response('Utilisateur non trouvé', 404);
        }

        // Récupérer un covoiturage existant (id = 1)
        $carpool = $em->getRepository(Carpool::class)->find(1);
        if (!$carpool) {
            return new Response('Covoiturage non trouvé', 404);
        }

        // --- DÉBUT DE LA MODIFICATION ---
        // Assure-toi que les dates n'ont pas d'heure pour les champs de type DATE_MUTABLE
        $departureDate = (new \DateTime())->setTime(0, 0, 0);
        $departureTime = (new \DateTime())->setTime(10, 0, 0); // Exemple d'heure
        $arrivalDate = (new \DateTime())->modify('+1 day')->setTime(0, 0, 0);
        $arrivalTime = (new \DateTime())->setTime(12, 0, 0); // Exemple d'heure

        // Mettre à jour le covoiturage avec les dates et heures séparées
        $carpool->setDepartureDate($departureDate);
        $carpool->setDepartureTime($departureTime);
        $carpool->setArrivalDate($arrivalDate);
        $carpool->setArrivalTime($arrivalTime);
        // --- FIN DE LA MODIFICATION ---

        // Créer une nouvelle réservation
        $booking = new Booking();
        $booking->setPassenger($user);
        $booking->setCarpool($carpool);
        $booking->setReservedSeats(2);
        $booking->setBookingDate(new \DateTime()); // La date de réservation peut avoir l'heure
        $booking->setStatus('en attente');

        // Persister et sauvegarder
        $em->persist($booking);
        $em->flush();

        return new Response('Réservation créée avec succès avec ID : ' . $booking->getId());
    }
}