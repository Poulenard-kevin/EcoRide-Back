<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\BookingRepository;
use App\Repository\CarpoolRepository;
use App\Repository\CarRepository;
use App\Repository\ReviewRepository;
use App\Entity\Carpool;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(
        BookingRepository $bookingRepository,
        CarpoolRepository $carpoolRepository,
        CarRepository $carRepository,
        ReviewRepository $reviewRepository
    ): Response {
        if ($this->getUser()) {
            $user = $this->getUser();

            $myBookings = $bookingRepository->findBy(['passenger' => $user], ['bookingDate' => 'DESC'], 5);
            $myCarpools = $carpoolRepository->findBy(['driver' => $user], ['departureDate' => 'DESC'], 5);
            $myCars = $carRepository->findBy(['owner' => $user]);

            // 3 derniers avis reçus (pour lequel l'utilisateur est la cible) — uniquement validés
            $receivedReviews = $reviewRepository->findBy(
                ['target' => $user, 'validated' => true],
                ['date' => 'DESC'],
                3
            );

            // 3 derniers avis écrits par l'utilisateur (auteur) — tous statuts
            $authoredReviews = $reviewRepository->findBy(
                ['author' => $user],
                ['date' => 'DESC'],
                3
            );

            return $this->render('dashboard/index.html.twig', [
                'myBookings' => $myBookings,
                'myCarpools' => $myCarpools,
                'myCars' => $myCars,
                'receivedReviews' => $receivedReviews,
                'authoredReviews' => $authoredReviews,
                'STATUS_ACTIVE' => Carpool::STATUS_ACTIVE,
                'STATUS_COMPLETED' => Carpool::STATUS_COMPLETED,
                'STATUS_CANCELLED' => Carpool::STATUS_CANCELLED,
                'STATUS_ARCHIVED' => Carpool::STATUS_ARCHIVED,
            ]);
        }

        return $this->redirectToRoute('app_login');
    }
}