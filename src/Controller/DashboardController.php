<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\BookingRepository;
use App\Repository\CarpoolRepository;
use App\Repository\CarRepository;
use App\Entity\Carpool;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(
        BookingRepository $bookingRepository,
        CarpoolRepository $carpoolRepository,
        CarRepository $carRepository 
    ): Response {
        if ($this->getUser()) {
            $user = $this->getUser();

            $myBookings = $bookingRepository->findBy(['passenger' => $user], ['bookingDate' => 'DESC'], 5);
            $myCarpools = $carpoolRepository->findBy(['driver' => $user], ['departureDate' => 'DESC'], 5);
            $myCars = $carRepository->findBy(['owner' => $user]); 

            return $this->render('dashboard/index.html.twig', [
                'myBookings' => $myBookings,
                'myCarpools' => $myCarpools,
                'myCars' => $myCars,
                'STATUS_ACTIVE' => Carpool::STATUS_ACTIVE,
                'STATUS_COMPLETED' => Carpool::STATUS_COMPLETED,
                'STATUS_CANCELLED' => Carpool::STATUS_CANCELLED,
                'STATUS_ARCHIVED' => Carpool::STATUS_ARCHIVED,
            ]);
        }

        return $this->redirectToRoute('app_login');
    }
}