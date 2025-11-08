<?php

namespace App\Controller;

use App\Entity\Carpool;
use App\Form\CarpoolType;
use App\Repository\CarpoolRepository;
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
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $carpool = new Carpool();
        $form = $this->createForm(CarpoolType::class, $carpool);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($carpool);
            $entityManager->flush();

            return $this->redirectToRoute('app_carpool_index', [], Response::HTTP_SEE_OTHER);
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
