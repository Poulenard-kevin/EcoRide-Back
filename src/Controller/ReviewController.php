<?php

namespace App\Controller;

use App\Entity\Review;
use App\Entity\Booking;
use App\Entity\User;
use App\Form\ReviewType;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/")
 */
class ReviewController extends AbstractController
{
    /**
     * Liste tous les avis — accessible aux admins et employés (modération)
     * @Route("/reviews", name="review_index", methods={"GET"})
     */
    public function index(Request $request, ReviewRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Autoriser admin ou employé (ATTENTION: ROLE_EMPLOYE en FR)
        if (!($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_EMPLOYE'))) {
            throw $this->createAccessDeniedException();
        }

        // ?pending=1 => afficher uniquement en attente, ?pending=0 => tous
        $onlyNotValidated = $request->query->getBoolean('pending', true);
        if ($onlyNotValidated) {
            $reviews = $repo->findBy(['validated' => false], ['date' => 'DESC']);
        } else {
            $reviews = $repo->findBy([], ['date' => 'DESC']);
        }

        return $this->render('review/index.html.twig', [
            'reviews' => $reviews,
            'onlyNotValidated' => $onlyNotValidated,
        ]);
    }

    /**
     * Valider / dévalider un avis (POST)
     * @Route("/review/{id}/validate", name="review_validate", methods={"POST"})
     */
    public function validate(Review $review, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if (!($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_EMPLOYE'))) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('validate'.$review->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', "Jeton CSRF invalide.");
            return $this->redirectToRoute('review_index');
        }

        // toggle validation
        $review->setValidated(!$review->isValidated());
        $em->flush();

        $this->addFlash('success', $review->isValidated() ? 'Avis validé.' : 'Validation annulée.');
        // rester sur la liste des en attente
        return $this->redirectToRoute('review_index', ['pending' => 1]);
    }

    /**
     * Mes avis reçus (je suis chauffeur) — front
     * @Route("/me/reviews/received", name="review_my_received", methods={"GET"})
     */
    public function myReceived(ReviewRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        $reviews = $repo->findBy(['target' => $user, 'validated' => true], ['date' => 'DESC']);

        return $this->render('review/my_received.html.twig', [
            'reviews' => $reviews,
        ]);
    }

    /**
     * Mes avis émis (ce que j'ai laissé) — front
     * @Route("/me/reviews", name="review_my_authored", methods={"GET"})
     */
    public function myAuthored(ReviewRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        $reviews = $repo->findBy(['author' => $user], ['date' => 'DESC']);

        return $this->render('review/my_authored.html.twig', [
            'reviews' => $reviews,
        ]);
    }

    /**
     * Supprimer un avis (auteur ou employé/admin)
     * @Route("/review/{id}/delete", name="review_delete_front", methods={"POST"})
     */
    public function delete(Review $review, Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $isAuthor = $review->getAuthor() && $review->getAuthor()->getId() === $this->getUser()->getId();
        $isModerator = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_EMPLOYE');

        if (!$isAuthor && !$isModerator) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$review->getId(), $request->request->get('_token'))) {
            $em->remove($review);
            $em->flush();
            $this->addFlash('success', 'Avis supprimé.');
        } else {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
        }

        if ($isModerator) {
            return $this->redirectToRoute('review_index', ['pending' => 1]);
        }
        return $this->redirectToRoute('review_my_authored');
    }

    /**
     * Créer un nouvel avis (flow passager après validation du trajet)
     * @Route("/review/new/{driver}", name="app_review_new", methods={"GET","POST"})
     */
    public function new(Request $request, EntityManagerInterface $em, UserRepository $userRepo, ReviewRepository $reviewRepo): Response
    {
        $driverId = $request->attributes->get('driver');
        $bookingId = $request->query->get('booking') ?? $request->request->get('booking');

        $driver = $userRepo->find($driverId);
        if (!$driver) {
            throw $this->createNotFoundException('Conducteur introuvable.');
        }

        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour laisser un avis.');
        }

        if ($bookingId) {
            $booking = $em->getRepository(Booking::class)->find($bookingId);
            if (!$booking || $booking->getPassenger()->getId() !== $user->getId()) {
                throw $this->createAccessDeniedException('Réservation invalide pour laisser un avis.');
            }

            $existing = $reviewRepo->findOneBy(['author' => $user, 'booking' => $booking]);
            if ($existing) {
                $this->addFlash('info', 'Vous avez déjà laissé un avis pour cette réservation.');
                return $this->redirectToRoute('app_booking_show', ['id' => $booking->getId()]);
            }
        } else {
            $booking = null;
        }

        $review = new Review();
        $review->setAuthor($user);
        $review->setDriver($driver);

        if ($booking) {
            $review->setBooking($booking);
            if ($booking->getCarpool()) {
                $review->setCarpool($booking->getCarpool());
            }
        }

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $review->setDate(new \DateTime());
            $em->persist($review);
            $em->flush();

            $this->addFlash('success', 'Merci pour votre avis !');
            return $this->redirectToRoute('app_booking_show', ['id' => $booking ? $booking->getId() : 0]);
        }

        return $this->render('review/new.html.twig', [
            'form' => $form->createView(),
            'driver' => $driver,
            'booking' => $booking,
        ]);
    }

    /**
     * @Route("/review/{id}/edit", name="review_edit", methods={"GET","POST"})
     */
    public function edit(Review $review, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $isAuthor = $review->getAuthor() && $this->getUser() && $review->getAuthor()->getId() === $this->getUser()->getId();
        $isModerator = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_EMPLOYE');

        if (!$isAuthor && !$isModerator) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mettre à jour la date si nécessaire (optionnel)
            $review->setDate(new \DateTime());
            $em->flush();

            $this->addFlash('success', 'Avis mis à jour.');
            return $this->redirectToRoute('review_my_authored');
        }

        return $this->render('review/edit.html.twig', [
            'form' => $form->createView(),
            'review' => $review,
        ]);
    }
}