<?php

namespace App\Controller;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReviewController extends AbstractController
{
    /**
     * Liste tous les avis — accessible aux admins et employés (modération)
     * @Route("/reviews", name="review_index", methods={"GET"})
     */
    public function index(Request $request, ReviewRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if (!in_array('ROLE_ADMIN', $this->getUser()->getRoles()) && !in_array('ROLE_EMPLOYEE', $this->getUser()->getRoles())) {
            throw $this->createAccessDeniedException();
        }

        // Option : filtrer par validés ou non via query param
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
    public function validate(Review $review, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if (!in_array('ROLE_ADMIN', $this->getUser()->getRoles()) && !in_array('ROLE_EMPLOYEE', $this->getUser()->getRoles())) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('validate'.$review->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', "Jeton CSRF invalide.");
            return $this->redirectToRoute('review_index');
        }

        $review->setValidated(!$review->isValidated());
        $em->flush();
        $this->addFlash('success', 'Statut de l\'avis mis à jour.');

        return $this->redirectToRoute('review_index');
    }

    /**
     * Mes avis reçus (je suis chauffeur) — front
     * @Route("/me/reviews/received", name="review_my_received", methods={"GET"})
     */
    public function myReceived(ReviewRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        // Ne montrer que les avis validés
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
    public function delete(Review $review, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $isAuthor = $review->getAuthor() && $review->getAuthor()->getId() === $this->getUser()->getId();
        $isModerator = in_array('ROLE_ADMIN', $this->getUser()->getRoles()) || in_array('ROLE_EMPLOYEE', $this->getUser()->getRoles());

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

        // Rediriger : si modérateur -> index, sinon -> mes avis ou covoiturage
        if ($isModerator) {
            return $this->redirectToRoute('review_index');
        }
        return $this->redirectToRoute('review_my_authored');
    }
}