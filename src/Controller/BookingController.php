<?php

namespace App\Controller;

use App\Entity\Booking;
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

    #[Route('/new', name: 'app_booking_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, BookingRepository $bookingRepository): Response
    {
        $user = $this->getUser();
        $booking = new Booking();

        $form = $this->createForm(BookingType::class, $booking, [
            'is_edit' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $carpool = $booking->getCarpool();

            if (!$carpool) {
                $this->addFlash('error', 'Veuillez sélectionner un covoiturage.');
                return $this->renderForm('booking/new.html.twig', [
                    'booking' => $booking,
                    'form' => $form,
                ]);
            }

            // Vérifier que l'utilisateur n'est pas le conducteur
            if ($carpool->getDriver() === $user) {
                $this->addFlash('error', 'Vous ne pouvez pas réserver votre propre covoiturage.');
                return $this->renderForm('booking/new.html.twig', [
                    'booking' => $booking,
                    'form' => $form,
                ]);
            }

            // Vérifie si l'utilisateur a déjà une réservation pour ce covoiturage
            $existingBooking = $bookingRepository->findOneBy([
                'passenger' => $user,
                'carpool' => $carpool,
            ]);

            if ($existingBooking) {
                $this->addFlash('warning', 'Vous avez déjà une réservation pour ce covoiturage.');
                return $this->redirectToRoute('app_booking_show', ['id' => $existingBooking->getId()]);
            }

            // Utiliser une transaction pour éviter les deadlocks
            try {
                $entityManager->beginTransaction();

                // Vérifier qu'il reste assez de places disponibles
                if ($carpool->getAvailableSeats() < $booking->getReservedSeats()) {
                    $entityManager->rollback();
                    $this->addFlash('error', 'Il n\'y a pas assez de places disponibles pour ce covoiturage.');
                    return $this->renderForm('booking/new.html.twig', [
                        'booking' => $booking,
                        'form' => $form,
                    ]);
                }

                // Définir automatiquement les valeurs
                $booking->setPassenger($user);
                $booking->setStatus(Booking::STATUS_PENDING);
                $booking->setBookingDate(new \DateTime());

                // Décrémenter les places disponibles
                $carpool->setAvailableSeats($carpool->getAvailableSeats() - $booking->getReservedSeats());

                $entityManager->persist($booking);
                $entityManager->flush();
                $entityManager->commit();

                $this->addFlash('success', 'Réservation créée avec succès. En attente de confirmation du conducteur.');
                return $this->redirectToRoute('app_booking_index');

            } catch (\Exception $e) {
                $entityManager->rollback();
                $this->addFlash('error', 'Une erreur est survenue lors de la création de la réservation. Veuillez réessayer.');
                
                return $this->renderForm('booking/new.html.twig', [
                    'booking' => $booking,
                    'form' => $form,
                ]);
            }
        }

        return $this->renderForm('booking/new.html.twig', [
            'booking' => $booking,
            'form' => $form,
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
    public function confirm(Booking $booking, EntityManagerInterface $entityManager): Response
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
    #[Route('/{id}/refuse', name: 'app_booking_refuse', methods: ['POST'])]
    public function refuse(Booking $booking, EntityManagerInterface $entityManager): Response
    {
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('refuse' . $booking->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        // Vérifier que l'utilisateur connecté est bien le conducteur
        if ($booking->getCarpool()->getDriver() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas le conducteur de ce covoiturage.');
        }

        // Vérifier que la réservation peut être refusée
        if (!$booking->canBeRefused()) {
            $this->addFlash('error', 'Cette réservation ne peut pas être refusée.');
            return $this->redirectToRoute('app_carpool_show', ['id' => $booking->getCarpool()->getId()]);
        }

        try {
            $entityManager->beginTransaction();

            $booking->setStatus(Booking::STATUS_REFUSED);
            
            // Remettre les places disponibles
            $carpool = $booking->getCarpool();
            $carpool->setAvailableSeats($carpool->getAvailableSeats() + $booking->getReservedSeats());
            
            $entityManager->flush();
            $entityManager->commit();

            $this->addFlash('success', 'Réservation refusée. Les places ont été remises à disposition.');

        } catch (\Exception $e) {
            $entityManager->rollback();
            $this->addFlash('error', 'Une erreur est survenue.');
        }

        return $this->redirectToRoute('app_carpool_show', ['id' => $booking->getCarpool()->getId()]);
    }

    // ANNULER une réservation (passager ou conducteur)
    #[Route('/{id}/cancel', name: 'app_booking_cancel', methods: ['POST'])]
    public function cancel(Booking $booking, EntityManagerInterface $entityManager): Response
    {
        // ✅ Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('cancel' . $booking->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est soit le passager, soit le conducteur
        if ($booking->getPassenger() !== $user && $booking->getCarpool()->getDriver() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas annuler cette réservation.');
        }

        // Vérifier que la réservation peut être annulée
        if (!$booking->canBeCancelled()) {
            $this->addFlash('error', 'Cette réservation ne peut pas être annulée.');
            return $this->redirectToRoute('app_booking_show', ['id' => $booking->getId()]);
        }

        try {
            $entityManager->beginTransaction();

            $booking->setStatus(Booking::STATUS_CANCELLED);
            
            // Remettre les places disponibles si la réservation était confirmée
            if ($booking->isConfirmed()) {
                $carpool = $booking->getCarpool();
                $carpool->setAvailableSeats($carpool->getAvailableSeats() + $booking->getReservedSeats());
            }
            
            $entityManager->flush();
            $entityManager->commit();

            $this->addFlash('success', 'Réservation annulée avec succès.');

        } catch (\Exception $e) {
            $entityManager->rollback();
            $this->addFlash('error', 'Une erreur est survenue.');
        }

        // Rediriger selon qui a annulé
        if ($booking->getPassenger() === $user) {
            return $this->redirectToRoute('app_booking_index');
        } else {
            return $this->redirectToRoute('app_carpool_show', ['id' => $booking->getCarpool()->getId()]);
        }
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