<?php

namespace App\Controller;

use App\Entity\Carpool;
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
        return $this->render('carpool/index.html.twig', [
            'carpools' => $carpoolRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_carpool_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CarRepository $carRepository): Response
    {
        $user = $this->getUser();
        $userCars = $carRepository->findBy(['owner' => $user]);

        if (count($userCars) === 0) {
            $this->addFlash('warning', 'Vous devez créer une voiture avant de créer un covoiturage.');
            return $this->redirectToRoute('app_car_new');
        }

        $carpool = new Carpool();

        $form = $this->createForm(CarpoolType::class, $carpool, [
            'user_cars' => $userCars,
            'is_edit' => false, // Ajouté pour le CarpoolType modifié
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
           
            $carpool->setDriver($user);

            $carpool->setStatus(Carpool::STATUS_ACTIVE);

            $car = $carpool->getCar();

            // ✅ Vérifier qu’une voiture a bien été sélectionnée (déjà fait par NotBlank sur le champ 'car')
            // if (!$car) {
            //     $this->addFlash('error', 'Vous devez sélectionner une voiture.');
            //     return $this->redirectToRoute('app_carpool_new');
            // }
    
            if ($car->getSeats() <= 0) {
                $this->addFlash('error', 'Le véhicule sélectionné doit avoir au moins une place.');
                // Redirige vers le formulaire de création de covoiturage pour corriger
                return $this->redirectToRoute('app_carpool_new');
            }

            // ✅ Déterminer le nombre de places depuis la voiture
            $totalSeats = $car->getSeats();
            $carpool->setTotalSeats($totalSeats);
            $carpool->setAvailableSeats($totalSeats); // toutes dispo au départ

            $entityManager->persist($carpool);
            $entityManager->flush();

            $this->addFlash('success', 'Covoiturage créé avec succès.');
            return $this->redirectToRoute('app_carpool_index');
        }

        return $this->renderForm('carpool/new.html.twig', [
            'carpool' => $carpool,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_carpool_show', methods: ['GET'])]
    public function show(Carpool $carpool): Response
    {
        return $this->render('carpool/show.html.twig', [
            'carpool' => $carpool,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_carpool_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Carpool $carpool, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CarpoolType::class, $carpool);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_carpool_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('carpool/edit.html.twig', [
            'carpool' => $carpool,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_carpool_delete', methods: ['POST'])]
    public function delete(Request $request, Carpool $carpool, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$carpool->getId(), $request->request->get('_token'))) {
            $entityManager->remove($carpool);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_carpool_index', [], Response::HTTP_SEE_OTHER);
    }
}
