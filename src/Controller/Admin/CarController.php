<?php

namespace App\Controller\Admin;

use App\Entity\Car;
use App\Form\CarType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/car')]
class CarController extends AbstractController
{
    #[Route('/', name: 'admin_car_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $cars = $em->getRepository(Car::class)->findAll();

        return $this->render('admin/car/index.html.twig', [
            'cars' => $cars,
        ]);
    }

    #[Route('/new', name: 'admin_car_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $car = new Car();
        $form = $this->createForm(CarType::class, $car, ['is_admin' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($car);
            $entityManager->flush();

            return $this->redirectToRoute('admin_car_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('admin/car/new.html.twig', [
            'car' => $car,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_car_show', methods: ['GET'])]
    public function show(Car $car): Response
    {
        return $this->render('admin/car/show.html.twig', [
            'car' => $car,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_car_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Car $car, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CarType::class, $car, ['is_admin' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('admin_car_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('admin/car/edit.html.twig', [
            'car' => $car,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_car_delete', methods: ['POST'])]
    public function delete(Request $request, Car $car, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$car->getId(), $request->request->get('_token'))) {
            $entityManager->remove($car);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_car_index', [], Response::HTTP_SEE_OTHER);
    }
}