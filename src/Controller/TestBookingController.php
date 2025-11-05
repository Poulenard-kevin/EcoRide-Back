<?php

namespace App\Controller;

use App\Entity\Booking;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Routing\Annotation\Route;

class TestBookingController extends AbstractController
{
    #[Route('/test/booking-validation', name: 'test_booking_validation')]
    public function testBookingValidation(ValidatorInterface $validator): Response
    {
        $booking = new Booking();
        $booking->setReservedSeats(0); // Valeur invalide (doit être positive)
        $booking->setBookingDate(new \DateTime());
        $booking->setStatus('en attente');

        $errors = $validator->validate($booking);

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