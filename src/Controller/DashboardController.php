<?php

// src/Controller/DashboardController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\BookingRepository;
use App\Repository\CarpoolRepository;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(BookingRepository $bookingRepository, CarpoolRepository $carpoolRepository): Response
    {
        if ($this->getUser()) {
            $user = $this->getUser();
            $myBookings = $bookingRepository->findBy(['passenger' => $user], ['bookingDate' => 'DESC'], 5);
            $myCarpools = $carpoolRepository->findBy(['driver' => $user], ['departureDate' => 'DESC'], 5);

            return $this->render('dashboard/index.html.twig', [
                'myBookings' => $myBookings,
                'myCarpools' => $myCarpools,
            ]);
        }

        // Rediriger ou afficher une page pour les non-connectés
        return $this->redirectToRoute('app_login');
    }
}