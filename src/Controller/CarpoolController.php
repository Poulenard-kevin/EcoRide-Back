<?php

namespace App\Controller;

use App\Entity\Carpool;
use App\Entity\Booking;
use App\Form\CarpoolType;
use App\Repository\CarpoolRepository;
use App\Repository\CarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/carpool')]
class CarpoolController extends AbstractController
{
    #[Route('/', name: 'app_carpool_index', methods: ['GET'])]
    public function index(CarpoolRepository $carpoolRepository): Response
    {
        // Récupère tous les covoiturages publiés (tu peux affiner si tu veux exclure les drafts)
        $carpools = $carpoolRepository->findBy([], ['departureDate' => 'DESC']);

        $active = [];
        $archived = [];

        foreach ($carpools as $c) {
            if ($c->getStatus() === Carpool::STATUS_ARCHIVED) {
                $archived[] = $c;
            } else {
                $active[] = $c;
            }
        }

        return $this->render('carpool/index.html.twig', [
            'carpools_active'   => $active,
            'carpools_archived' => $archived,
        ]);
    }

    #[Route('/new', name: 'app_carpool_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $carpool = new Carpool();
        $form = $this->createForm(CarpoolType::class, $carpool, [
            'user_cars' => $this->getUser()->getCars(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Définir automatiquement le statut à "Actif"
            $carpool->setStatus(Carpool::STATUS_ACTIVE);
            $carpool->setDriver($this->getUser());

            // Récupérer la voiture sélectionnée
            $car = $carpool->getCar();

            if ($car && method_exists($car, 'getSeats')) {
                // Définir automatiquement le nombre total de places depuis la voiture
                $carpool->setTotalSeats($car->getSeats());
                
                // Initialiser les places disponibles à la capacité totale (modifiable plus tard si besoin)
                if (null === $carpool->getAvailableSeats()) {
                    $carpool->setAvailableSeats($car->getSeats());
                } elseif ($carpool->getAvailableSeats() > $car->getSeats()) {
                    // Correction si l'utilisateur a saisi manuellement un nombre trop élevé
                    $carpool->setAvailableSeats($car->getSeats());
                }
            } else {
                // Valeur par défaut si aucune voiture n’est sélectionnée (cas improbable grâce à NotBlank)
                $carpool->setTotalSeats(1);
                $carpool->setAvailableSeats(1);
            }
            
            $entityManager->persist($carpool);
            $entityManager->flush();

            $this->addFlash('success', 'Covoiturage créé avec succès.');
            return $this->redirectToRoute('app_carpool_show', ['id' => $carpool->getId()]);
        }

        return $this->render('carpool/new.html.twig', [
            'carpool' => $carpool,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_carpool_show', methods: ['GET'])]
    public function show(Carpool $carpool): Response
    {
        // si $carpool->getBookings() renvoie une Collection déjà disponible
        $bookings = $carpool->getBookings(); // retourne Collection => tu peux la passer telle quelle

        return $this->render('carpool/show.html.twig', [
            'carpool' => $carpool,
            'bookings' => $bookings,
            'STATUS_AWAITING' => Booking::STATUS_AWAITING_VALIDATION,
            'STATUS_ONGOING' => Carpool::STATUS_ONGOING,
            'STATUS_ACTIVE' => Carpool::STATUS_ACTIVE,
            'STATUS_STARTED' => Carpool::STATUS_STARTED,
            'STATUS_COMPLETED' => Carpool::STATUS_COMPLETED,
            'STATUS_CANCELLED' => Carpool::STATUS_CANCELLED,
            'STATUS_ARCHIVED' => Carpool::STATUS_ARCHIVED,
        ]);
    }

    #[Route('/{id}/start', name: 'app_carpool_start', methods: ['POST'])]
    public function start(Request $request, Carpool $carpool, EntityManagerInterface $entityManager): Response
    {
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('start' . $carpool->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        // Vérifier que l'utilisateur est bien le conducteur
        if ($carpool->getDriver() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas démarrer ce covoiturage.');
        }

        // Vérifier que le covoiturage est actif (prêt à démarrer)
        if ($carpool->getStatus() !== Carpool::STATUS_ACTIVE) {
            $this->addFlash('error', 'Ce covoiturage ne peut pas être démarré.');
            return $this->redirectToRoute('app_carpool_show', ['id' => $carpool->getId()], Response::HTTP_SEE_OTHER);
        }

        // Mettre à jour le statut en "started"
        $carpool->setStatus(Carpool::STATUS_STARTED);
        $entityManager->flush();

        $this->addFlash('success', 'Covoiturage démarré avec succès.');
        return $this->redirectToRoute('app_carpool_show', ['id' => $carpool->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/edit', name: 'app_carpool_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Carpool $carpool, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est bien le conducteur
        if ($carpool->getDriver() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce covoiturage.');
        }

        $form = $this->createForm(CarpoolType::class, $carpool, [
            'user_cars' => $this->getUser()->getCars(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Covoiturage modifié avec succès.');
            return $this->redirectToRoute('app_carpool_show', ['id' => $carpool->getId()]);
        }

        return $this->render('carpool/edit.html.twig', [
            'carpool' => $carpool,
            'form' => $form,
        ]);
    }

    // TERMINER un covoiturage (et l'archiver automatiquement)
    #[Route('/{id}/complete', name: 'app_carpool_complete', methods: ['POST'])]
    public function complete(Request $request, Carpool $carpool, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('complete' . $carpool->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        if ($carpool->getDriver() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas terminer ce covoiturage.');
        }

        // Vérifie que le covoiturage est bien "en cours"
        if ($carpool->getStatus() !== Carpool::STATUS_STARTED) {
            $this->addFlash('error', 'Ce covoiturage ne peut pas être terminé car il n’est pas en cours.');
            return $this->redirectToRoute('app_carpool_show', ['id' => $carpool->getId()]);
        }

        // Passe en "terminé" puis immédiatement en "archivé"
        $carpool->setStatus(Carpool::STATUS_COMPLETED);

        // Petite pause logique : tu pourrais vouloir afficher “terminé” avant l’archivage automatique
        // mais ici, on fait les deux d’un coup comme demandé :
        $carpool->setStatus(Carpool::STATUS_ARCHIVED);

        $entityManager->flush();

        $this->addFlash('success', 'Covoiturage terminé et archivé avec succès.');
        return $this->redirectToRoute('app_carpool_index');
    }

    #[Route('/{id}', name: 'app_carpool_delete', methods: ['POST'])]
    public function delete(Request $request, Carpool $carpool, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete' . $carpool->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        if ($carpool->getDriver() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce covoiturage.');
        }

        $conn = $entityManager->getConnection();
        $conn->beginTransaction();

        try {
            foreach ($carpool->getBookings() as $booking) {
                $entityManager->remove($booking);
            }

            $entityManager->remove($carpool);
            $entityManager->flush();
            $conn->commit();

            $this->addFlash('success', 'Covoiturage et réservations associées supprimés avec succès.');
        } catch (\Throwable $e) {
            $conn->rollBack();
            // loger $e si nécessaire
            $this->addFlash('error', 'Erreur lors de la suppression. Aucune modification effectuée.');
        }

        return $this->redirectToRoute('app_carpool_index', [], Response::HTTP_SEE_OTHER);
    }

    // ANNULER un covoiturage (conducteur uniquement)
    #[Route('/{id}/cancel', name: 'app_carpool_cancel', methods: ['POST'])]
    public function cancel(Request $request, Carpool $carpool, EntityManagerInterface $entityManager): Response
    {
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('cancel' . $carpool->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        // Vérifier que l'utilisateur est bien le conducteur
        if ($carpool->getDriver() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas annuler ce covoiturage.');
        }

        // Vérifier que le covoiturage peut être annulé
        if ($carpool->getStatus() !== Carpool::STATUS_ACTIVE) {
            $this->addFlash('error', 'Ce covoiturage ne peut pas être annulé.');
            return $this->redirectToRoute('app_carpool_show', ['id' => $carpool->getId()], Response::HTTP_SEE_OTHER);
        }

        $carpool->setStatus(Carpool::STATUS_CANCELLED);
        $entityManager->flush();

        $this->addFlash('success', 'Covoiturage annulé avec succès.');
        return $this->redirectToRoute('app_carpool_show', ['id' => $carpool->getId()], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/carpool/{id}/finish", name="carpool_finish", methods={"POST"})
     */
    public function finish(Carpool $carpool, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        // Sécurité : seul le conducteur peut terminer
        if ($carpool->getDriver()->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('finish'.$carpool->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_carpool_show', ['id' => $carpool->getId()]);
        }

        // Archiver le covoiturage
        $carpool->setStatus(Carpool::STATUS_ARCHIVED);
        $carpool->setArchivedAt(new \DateTime());

        // Mettre les réservations confirmées en attente de validation par passagers
        foreach ($carpool->getBookings() as $booking) {
            if ($booking->getStatus() === Booking::STATUS_CONFIRMED) {
                $booking->setStatus(Booking::STATUS_AWAITING_VALIDATION);
            }
        }

        $em->flush();

        $this->addFlash('success', 'Covoiturage terminé et archivé. Les passagers peuvent désormais valider leurs trajets.');
        return $this->redirectToRoute('app_carpool_show', ['id' => $carpool->getId()]);
    }


    // ARCHIVER un covoiturage (conducteur uniquement)
    #[Route('/{id}/archive', name: 'app_carpool_archive', methods: ['POST'])]
    public function archive(Request $request, Carpool $carpool, EntityManagerInterface $entityManager): Response
    {
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('archive' . $carpool->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        // Vérifier que l'utilisateur est bien le conducteur
        if ($carpool->getDriver() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas archiver ce covoiturage.');
        }

        $carpool->setStatus(Carpool::STATUS_ARCHIVED);
        $entityManager->flush();

        $this->addFlash('success', 'Covoiturage archivé avec succès.');
        return $this->redirectToRoute('app_carpool_index');
    }
}
