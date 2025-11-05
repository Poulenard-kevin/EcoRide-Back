<?php

namespace App\Controller;

use App\Entity\Reservation;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;

class TestReservationController extends AbstractController
{
    #[Route('/test/reservation-validation', name: 'test_reservation_validation')]
    public function testReservationValidation(ValidatorInterface $validator): Response
    {
        $reservation = new Reservation();
        $reservation->setNbPlacesReservees(0); // Valeur invalide (doit être positive)
        $reservation->setDateReservation(new \DateTime());
        $reservation->setStatut('en attente');

        $errors = $validator->validate($reservation);

        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return new Response('Erreurs de validation :<br>' . implode('<br>', $messages));
        }

        return new Response('Validation OK, réservation valide.');
    }
}