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
        
        // Afficher uniquement les réservations de l'utilisateur connecté
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

        $form = $this->createForm(BookingType::class, $booking, [
            'is_edit' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Ne pas permettre au conducteur de réserver son propre trajet
            if ($carpool->getDriver() === $user) {
                $this->addFlash('error', 'Vous ne pouvez pas réserver votre propre covoiturage.');
                return $this->renderForm('booking/new.html.twig', [
                    'booking' => $booking,
                    'form' => $form,
                    'carpool' => $carpool,
                ]);
            }

            // Normaliser reservedSeats (sécurité)
            $reservedSeats = (int) $booking->getReservedSeats();
            if ($reservedSeats <= 0) {
                $reservedSeats = 1;
                $booking->setReservedSeats($reservedSeats);
            }

            // Vérifier si l'utilisateur a déjà une réservation
            $existingBooking = $bookingRepository->findOneBy([
                'passenger' => $user,
                'carpool' => $carpool,
            ]);
            if ($existingBooking) {
                $this->addFlash('warning', 'Vous avez déjà une réservation pour ce covoiturage.');
                return $this->redirectToRoute('app_booking_show', ['id' => $existingBooking->getId()]);
            }

            // Vérifier qu'il reste assez de places disponibles (FAIRE AVANT la transaction)
            if ($carpool->getAvailableSeats() < $reservedSeats) {
                $this->addFlash('error', 'Il n\'y a pas assez de places disponibles pour ce covoiturage.');
                return $this->renderForm('booking/new.html.twig', [
                    'booking' => $booking,
                    'form' => $form,
                    'carpool' => $carpool,
                ]);
            }

            // Démarrer la transaction et persister
            $conn = $entityManager->getConnection();
            $conn->beginTransaction();

            try {
                $booking->setPassenger($user);
                $booking->setStatus(Booking::STATUS_PENDING);
                $booking->setBookingDate(new \DateTime());

                // Persister la réservation
                $entityManager->persist($booking);
                $entityManager->flush();

                // --- RECALCULER les places occupées et availableSeats ---
                $statusesToCount = [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED];
                $occupied = $bookingRepository->sumReservedSeatsByCarpoolAndStatuses($carpool, $statusesToCount);

                $totalSeats = $carpool->getTotalSeats();
                if ($totalSeats !== null) {
                    $newAvailable = max(0, $totalSeats - $occupied);
                    $carpool->setAvailableSeats($newAvailable);
                    $entityManager->flush();
                }

                $conn->commit();

                $this->addFlash('success', 'Réservation créée avec succès. En attente de confirmation du conducteur.');

                return $this->redirectToRoute('app_booking_index');
            } catch (\Throwable $e) {
                if ($conn->isTransactionActive()) {
                    $conn->rollBack();
                }
                // optionnel : logger l'exception
                $this->addFlash('error', 'Une erreur est survenue lors de la création de la réservation. Veuillez réessayer.');

                return $this->renderForm('booking/new.html.twig', [
                    'booking' => $booking,
                    'form' => $form,
                    'carpool' => $carpool,
                ]);
            }
        }

        // Affichage du formulaire (GET ou form non validé)
        return $this->renderForm('booking/new.html.twig', [
            'booking' => $booking,
            'form' => $form,
            'carpool' => $carpool,
        ]);
    }

    #[Route('/{id}', name: 'app_booking_show', methods: ['GET'])]
    public function show(Booking $booking): Response
    {
        // Vérifier que l'utilisateur est soit le passager, soit le conducteur
        $user = $this->getUser();
        if ($booking->getPassenger() !== $user && $booking->getCarpool()->getDriver() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette réservation.');
        }

        return $this->render('booking/show.html.twig', [
            'booking' => $booking,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_booking_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Booking $booking, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est le passager
        if ($booking->getPassenger() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cette réservation.');
        }

        // Vérifier que la réservation peut être modifiée
        if (!$booking->canBeCancelled()) {
            $this->addFlash('error', 'Cette réservation ne peut plus être modifiée.');
            return $this->redirectToRoute('app_booking_show', ['id' => $booking->getId()]);
        }

        $originalReservedSeats = $booking->getReservedSeats();

        // Passer is_edit: true pour afficher le statut
        $form = $this->createForm(BookingType::class, $booking, [
            'is_edit' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->beginTransaction();

                $carpool = $booking->getCarpool();
                $seatsDifference = $booking->getReservedSeats() - $originalReservedSeats;

                // Vérifier qu'il y a assez de places disponibles
                if ($seatsDifference > 0 && $carpool->getAvailableSeats() < $seatsDifference) {
                    $entityManager->rollback();
                    $this->addFlash('error', 'Il n\'y a pas assez de places disponibles.');
                    return $this->renderForm('booking/edit.html.twig', [
                        'booking' => $booking,
                        'form' => $form,
                    ]);
                }

                // Ajuster les places disponibles
                $carpool->setAvailableSeats($carpool->getAvailableSeats() - $seatsDifference);

                $entityManager->flush();
                $entityManager->commit();

                $this->addFlash('success', 'Réservation modifiée avec succès.');
                return $this->redirectToRoute('app_booking_index');

            } catch (\Exception $e) {
                $entityManager->rollback();
                $this->addFlash('error', 'Une erreur est survenue lors de la modification.');
            }
        }

        return $this->renderForm('booking/edit.html.twig', [
            'booking' => $booking,
            'form' => $form,
        ]);
    }

    // CONFIRMER une réservation (conducteur uniquement)
    #[Route('/{id}/confirm', name: 'app_booking_confirm', methods: ['POST'])]
    public function confirm(Request $request, Booking $booking, EntityManagerInterface $entityManager): Response
    {
        // ✅ Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('confirm' . $booking->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }
        
        // Vérifier que l'utilisateur connecté est bien le conducteur
        if ($booking->getCarpool()->getDriver() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas le conducteur de ce covoiturage.');
        }

        // Vérifier que la réservation peut être confirmée
        if (!$booking->canBeConfirmed()) {
            $this->addFlash('error', 'Cette réservation ne peut pas être confirmée.');
            return $this->redirectToRoute('app_carpool_show', ['id' => $booking->getCarpool()->getId()]);
        }

        $booking->setStatus(Booking::STATUS_CONFIRMED);
        $entityManager->flush();

        $this->addFlash('success', 'Réservation confirmée avec succès.');
        return $this->redirectToRoute('app_carpool_show', ['id' => $booking->getCarpool()->getId()]);
    }

    // REFUSER une réservation (conducteur uniquement)
    #[Route('/booking/{id}/refuse', name: 'app_booking_refuse', methods: ['POST'])]
    public function refuse(Request $request, Booking $booking, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('refuse' . $booking->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        // Vérifier droit : conducteur du covoiturage ou ROLE_ADMIN
        $carpool = $booking->getCarpool();
        if ($carpool->getDriver() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas refuser cette réservation.');
        }

        try {
            $em->beginTransaction();

            // Remettre les places si la réservation a diminué availableSeats (pending ou confirmed)
            if ($booking->isPending() || $booking->isConfirmed()) {
                $newAvailable = $carpool->getAvailableSeats() + $booking->getReservedSeats();

                // S'assurer de ne pas dépasser totalSeats
                $totalSeats = $carpool->getTotalSeats() ?: null;
                if ($totalSeats !== null) {
                    $newAvailable = min($newAvailable, $totalSeats);
                }

                $carpool->setAvailableSeats($newAvailable);
            }

            $booking->setStatus(Booking::STATUS_REFUSED);
            $em->flush();
            $em->commit();

            $this->addFlash('success', 'Réservation refusée.');
        } catch (\Throwable $e) {
            $em->rollback();
            // optionnel : logger l'exception ici
            $this->addFlash('error', 'Une erreur est survenue lors du refus de la réservation.');
        }

        return $this->redirectToRoute('app_carpool_show', ['id' => $carpool->getId()]);
    }

    // ANNULER une réservation (passager ou conducteur)
    #[Route('/{id}/cancel', name: 'app_booking_cancel', methods: ['POST'])]
    public function cancel(Request $request, Booking $booking, EntityManagerInterface $entityManager, BookingRepository $bookingRepository): Response
    {
        if (!$this->isCsrfTokenValid('cancel' . $booking->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $user = $this->getUser();
        $carpool = $booking->getCarpool();

        // Autorisations (comme précédemment)
        if ($booking->isConfirmed()) {
            if ($carpool->getDriver() !== $user && !$this->isGranted('ROLE_ADMIN')) {
                throw $this->createAccessDeniedException('Seul le conducteur (ou un admin) peut annuler une réservation confirmée.');
            }
        } else {
            if ($booking->getPassenger() !== $user && $carpool->getDriver() !== $user && !$this->isGranted('ROLE_ADMIN')) {
                throw $this->createAccessDeniedException('Vous ne pouvez pas annuler cette réservation.');
            }
        }

        if (!$booking->canBeCancelled()) {
            $this->addFlash('error', 'Cette réservation ne peut pas être annulée.');
            return $this->redirectToRoute('app_booking_show', ['id' => $booking->getId()]);
        }

        $conn = $entityManager->getConnection();
        $conn->beginTransaction();

        try {
            // mémoriser état si besoin (pas nécessaire si on recalcule après)
            $booking->setStatus(Booking::STATUS_CANCELLED);

            $entityManager->flush();

            // --- RECALCULER les places occupées puis availableSeats ---
            // Choisis les statuts que tu veux considérer comme "occupant" une place.
            // Si tu réserves et décrémente dès pending, inclut 'pending' et 'confirmed'
            $statusesToCount = [Booking::STATUS_PENDING, Booking::STATUS_CONFIRMED];

            $occupied = $bookingRepository->sumReservedSeatsByCarpoolAndStatuses($carpool, $statusesToCount);

            $totalSeats = $carpool->getTotalSeats();
            if ($totalSeats !== null) {
                $newAvailable = max(0, $totalSeats - $occupied);
            } else {
                // fallback : si pas de totalSeats, on remet selon valeur courante + reservedSeats retirée
                // mais idéalement totalSeats doit être défini
                $newAvailable = max(0, $carpool->getAvailableSeats());
            }

            $carpool->setAvailableSeats($newAvailable);
            $entityManager->flush();

            $conn->commit();

            $this->addFlash('success', 'Réservation annulée avec succès.');
        } catch (\Throwable $e) {
            if ($conn->isTransactionActive()) {
                $conn->rollBack();
            }
            // logger si possible
            $this->addFlash('error', 'Une erreur est survenue lors de l\'annulation.');
        }

        if ($booking->getPassenger() === $user) {
            return $this->redirectToRoute('app_booking_index');
        }

        return $this->redirectToRoute('app_carpool_show', ['id' => $carpool->getId()]);
    }

    #[Route('/{id}/delete', name: 'app_booking_delete', methods: ['POST'])]
    public function delete(Request $request, Booking $booking, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est le passager ou un admin
        $user = $this->getUser();
        if ($booking->getPassenger() !== $user && !in_array('ROLE_ADMIN', $user->getRoles())) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cette réservation.');
        }

        if ($this->isCsrfTokenValid('delete'.$booking->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->beginTransaction();

                // Remettre les places disponibles dans le covoiturage si la réservation était confirmée
                if ($booking->isConfirmed() || $booking->isPending()) {
                    $carpool = $booking->getCarpool();
                    if ($carpool) {
                        $carpool->setAvailableSeats($carpool->getAvailableSeats() + $booking->getReservedSeats());
                    }
                }

                $entityManager->remove($booking);
                $entityManager->flush();
                $entityManager->commit();

                $this->addFlash('success', 'Réservation supprimée avec succès.');

            } catch (\Exception $e) {
                $entityManager->rollback();
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression. Veuillez réessayer.');
            }
        }

        return $this->redirectToRoute('app_booking_index', [], Response::HTTP_SEE_OTHER);
    }
}