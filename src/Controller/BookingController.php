<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Carpool;
use App\Form\BookingType;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/booking')]
class BookingController extends AbstractController
{
    #[Route('/', name: 'app_booking_index', methods: ['GET'])]
    public function index(BookingRepository $bookingRepository): Response
    {
        $user = $this->getUser();
        $bookings = $bookingRepository->findBy(['passenger' => $user]);

        return $this->render('booking/index.html.twig', [
            'bookings' => $bookings,
        ]);
    }

    #[Route('/new/{carpool}', name: 'app_booking_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Carpool $carpool, EntityManagerInterface $entityManager, BookingRepository $bookingRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour réserver.');
        }

        $booking = new Booking();
        $booking->setCarpool($carpool);

        $form = $this->createForm(BookingType::class, $booking, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($carpool->getDriver() === $user) {
                $this->addFlash('error', 'Vous ne pouvez pas réserver votre propre covoiturage.');
                return $this->renderForm('booking/new.html.twig', [
                    'booking' => $booking,
                    'form' => $form,
                    'carpool' => $carpool,
                ]);
            }

            $reservedSeats = max(1, (int)$booking->getReservedSeats());

            $existingBooking = $bookingRepository->findOneBy([
                'passenger' => $user,
                'carpool' => $carpool,
            ]);
            if ($existingBooking) {
                $this->addFlash('warning', 'Vous avez déjà une réservation pour ce covoiturage. Si vous voulez réserver à nouveau, veuillez supprimer d\'abord votre réservation actuelle.');
                return $this->redirectToRoute('app_booking_show', ['id' => $existingBooking->getId()]);
            }

            // calculer les places réellement disponibles depuis la base
            $statusesToCount = [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED];
            $occupied = $bookingRepository->sumReservedSeatsByCarpoolAndStatuses($carpool, $statusesToCount);

            // récupérer totalSeats (fallback sur la voiture si nécessaire)
            $totalSeats = $carpool->getTotalSeats();
            if ($totalSeats === null && method_exists($carpool, 'getCar') && $carpool->getCar()) {
                $vehicle = $carpool->getCar();
                if (method_exists($vehicle, 'getSeats')) {
                    $totalSeats = $vehicle->getSeats();
                } elseif (method_exists($vehicle, 'getNbPlaces')) {
                    $totalSeats = $vehicle->getNbPlaces();
                }
            }

            $available = $totalSeats !== null ? max(0, $totalSeats - $occupied) : 0;

            if ($available < $reservedSeats) {
                $this->addFlash('error', 'Il n\'y a pas assez de places disponibles ('.$available.').');
                return $this->renderForm('booking/new.html.twig', [
                    'booking' => $booking,
                    'form' => $form,
                    'carpool' => $carpool,
                ]);
            }

            $conn = $entityManager->getConnection();
            $conn->beginTransaction();

            try {
                $booking->setPassenger($user);
                $booking->setStatus(Booking::STATUS_PENDING);
                $booking->setBookingDate(new \DateTime());
                $booking->setReservedSeats($reservedSeats);

                $entityManager->persist($booking);
                $entityManager->flush();

                $this->recalculateAvailableSeats($carpool, $bookingRepository, $entityManager);

                $conn->commit();
                $this->addFlash('success', 'Réservation créée avec succès.');
                return $this->redirectToRoute('app_booking_index');
            } catch (\Throwable $e) {
                if ($conn->isTransactionActive()) {
                    $conn->rollBack();
                }
                $this->addFlash('error', 'Erreur lors de la création de la réservation.');
                return $this->renderForm('booking/new.html.twig', [
                    'booking' => $booking,
                    'form' => $form,
                    'carpool' => $carpool,
                ]);
            }
        }

        return $this->renderForm('booking/new.html.twig', [
            'booking' => $booking,
            'form' => $form,
            'carpool' => $carpool,
        ]);
    }

    #[Route('/{id}', name: 'app_booking_show', methods: ['GET'])]
    public function show(Booking $booking): Response
    {
        $user = $this->getUser();
        if ($booking->getPassenger() !== $user && $booking->getCarpool()->getDriver() !== $user) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        return $this->render('booking/show.html.twig', [
            'booking' => $booking,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_booking_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Booking $booking, EntityManagerInterface $entityManager, BookingRepository $bookingRepository): Response
    {
        if ($booking->getPassenger() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        if (!$booking->canBeCancelled()) {
            $this->addFlash('error', 'Modification impossible.');
            return $this->redirectToRoute('app_booking_show', ['id' => $booking->getId()]);
        }

        $originalReservedSeats = $booking->getReservedSeats();
        $form = $this->createForm(BookingType::class, $booking, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $conn = $entityManager->getConnection();
            $conn->beginTransaction();

            try {
                $carpool = $booking->getCarpool();
                $diff = $booking->getReservedSeats() - $originalReservedSeats;

                if ($diff > 0 && $carpool->getAvailableSeats() < $diff) {
                    $conn->rollBack();
                    $this->addFlash('error', 'Pas assez de places disponibles.');
                    return $this->renderForm('booking/edit.html.twig', [
                        'booking' => $booking,
                        'form' => $form,
                    ]);
                }

                $entityManager->flush();

                // Recalculer après modification
                $this->recalculateAvailableSeats($carpool, $bookingRepository, $entityManager);

                $conn->commit();
                $this->addFlash('success', 'Réservation modifiée.');
                return $this->redirectToRoute('app_booking_index');
            } catch (\Throwable $e) {
                if ($conn->isTransactionActive()) {
                    $conn->rollBack();
                }
                $this->addFlash('error', 'Erreur lors de la modification.');
            }
        }

        return $this->renderForm('booking/edit.html.twig', [
            'booking' => $booking,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/confirm', name: 'app_booking_confirm', methods: ['POST'])]
    public function confirm(Request $request, Booking $booking, EntityManagerInterface $entityManager, BookingRepository $bookingRepository): Response
    {
        if (!$this->isCsrfTokenValid('confirm' . $booking->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $carpool = $booking->getCarpool();
        if ($carpool->getDriver() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        if (!$booking->canBeConfirmed()) {
            $this->addFlash('error', 'Impossible de confirmer.');
            return $this->redirectToRoute('app_carpool_show', ['id' => $carpool->getId()]);
        }

        $conn = $entityManager->getConnection();
        $conn->beginTransaction();

        try {
            $booking->setStatus(Booking::STATUS_CONFIRMED);
            $entityManager->flush();

            $this->recalculateAvailableSeats($carpool, $bookingRepository, $entityManager);

            $conn->commit();
            $this->addFlash('success', 'Réservation confirmée.');
        } catch (\Throwable $e) {
            if ($conn->isTransactionActive()) {
                $conn->rollBack();
            }
            $this->addFlash('error', 'Erreur lors de la confirmation.');
        }

        return $this->redirectToRoute('app_carpool_show', ['id' => $carpool->getId()]);
    }

    #[Route('/{id}/refuse', name: 'app_booking_refuse', methods: ['POST'])]
    public function refuse(Request $request, Booking $booking, EntityManagerInterface $entityManager, BookingRepository $bookingRepository): Response
    {
        if (!$this->isCsrfTokenValid('refuse' . $booking->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $carpool = $booking->getCarpool();
        if ($carpool->getDriver() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $conn = $entityManager->getConnection();
        $conn->beginTransaction();

        try {
            $booking->setStatus(Booking::STATUS_REFUSED);
            $entityManager->flush();

            $this->recalculateAvailableSeats($carpool, $bookingRepository, $entityManager);

            $conn->commit();
            $this->addFlash('success', 'Réservation refusée.');
        } catch (\Throwable $e) {
            if ($conn->isTransactionActive()) {
                $conn->rollBack();
            }
            $this->addFlash('error', 'Erreur lors du refus.');
        }

        return $this->redirectToRoute('app_carpool_show', ['id' => $carpool->getId()]);
    }

    #[Route('/{id}/cancel', name: 'app_booking_cancel', methods: ['POST'])]
    public function cancel(Request $request, Booking $booking, EntityManagerInterface $entityManager, BookingRepository $bookingRepository): Response
    {
        if (!$this->isCsrfTokenValid('cancel' . $booking->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $user = $this->getUser();
        $carpool = $booking->getCarpool();

        if ($booking->isConfirmed()) {
            if ($carpool->getDriver() !== $user && !$this->isGranted('ROLE_ADMIN')) {
                throw $this->createAccessDeniedException('Seul le conducteur peut annuler.');
            }
        } else {
            if ($booking->getPassenger() !== $user && $carpool->getDriver() !== $user && !$this->isGranted('ROLE_ADMIN')) {
                throw $this->createAccessDeniedException('Accès refusé.');
            }
        }

        if (!$booking->canBeCancelled()) {
            $this->addFlash('error', 'Annulation impossible.');
            return $this->redirectToRoute('app_booking_show', ['id' => $booking->getId()]);
        }

        $conn = $entityManager->getConnection();
        $conn->beginTransaction();

        try {
            $booking->setStatus(Booking::STATUS_CANCELLED);
            $entityManager->flush();

            $this->recalculateAvailableSeats($carpool, $bookingRepository, $entityManager);

            $conn->commit();
            $this->addFlash('success', 'Réservation annulée.');
        } catch (\Throwable $e) {
            if ($conn->isTransactionActive()) {
                $conn->rollBack();
            }
            $this->addFlash('error', 'Erreur lors de l\'annulation.');
        }

        if ($booking->getPassenger() === $user) {
            return $this->redirectToRoute('app_booking_index');
        }

        return $this->redirectToRoute('app_carpool_show', ['id' => $carpool->getId()]);
    }

    #[Route('/{id}/delete', name: 'app_booking_delete', methods: ['POST'])]
    public function delete(Request $request, Booking $booking, EntityManagerInterface $entityManager, BookingRepository $bookingRepository): Response
    {
        $user = $this->getUser();
        if ($booking->getPassenger() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        if ($this->isCsrfTokenValid('delete'.$booking->getId(), $request->request->get('_token'))) {
            $carpool = $booking->getCarpool();

            $conn = $entityManager->getConnection();
            $conn->beginTransaction();

            try {
                $entityManager->remove($booking);
                $entityManager->flush();

                if ($carpool) {
                    $this->recalculateAvailableSeats($carpool, $bookingRepository, $entityManager);
                }

                $conn->commit();
                $this->addFlash('success', 'Réservation supprimée.');
            } catch (\Throwable $e) {
                if ($conn->isTransactionActive()) {
                    $conn->rollBack();
                }
                $this->addFlash('error', 'Erreur lors de la suppression.');
            }
        }

        return $this->redirectToRoute('app_booking_index');
    }

    // Méthode privée de recalcul centralisée
    private function recalculateAvailableSeats(Carpool $carpool, BookingRepository $bookingRepository, EntityManagerInterface $entityManager): void
    {
        $statusesToCount = [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED];
        $occupied = $bookingRepository->sumReservedSeatsByCarpoolAndStatuses($carpool, $statusesToCount);

        $totalSeats = $carpool->getTotalSeats();
        // fallback : essayer depuis le véhicule si présent
        if ($totalSeats === null && method_exists($carpool, 'getVehicle') && $carpool->getVehicle()) {
            $vehicle = $carpool->getVehicle();
            if (method_exists($vehicle, 'getSeats')) {
                $totalSeats = $vehicle->getSeats();
            } elseif (method_exists($vehicle, 'getNbPlaces')) {
                $totalSeats = $vehicle->getNbPlaces();
            }
        }

        if ($totalSeats === null) {
            // Avertissement en dev : on ne sait pas calculer correctement
            // Ici on ne casse rien : on laisse availableSeats tel quel et on logge
            if (method_exists($this, 'getParameter')) { // pour éviter erreur si pas de logger
                // nothing
            }
            // Optionnel : throw new \RuntimeException('totalSeats missing for carpool '.$carpool->getId());
        } else {
            $newAvailable = max(0, $totalSeats - $occupied);
            $carpool->setAvailableSeats($newAvailable);
            $entityManager->persist($carpool);
            $entityManager->flush();
        }
    }
}