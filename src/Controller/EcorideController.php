<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('api/ecoride', name: 'api_ecoride_')]

class EcorideController extends AbstractController
{
    #[Route(methods: 'POST', name: 'new')]
    public function new(): Response
    {
        return $this->json(['message' => 'Resource created successfully']);
    }

    #[Route('/', methods: 'GET', name: 'show')]
    public function show(): Response
    {
        return $this->json(['message' => 'Resource retrieved successfully']);
    }

    #[Route('/', methods: 'PUT', name: 'edit')]
    public function edit(): Response
    {
        return $this->json(['message' => 'Resource updated successfully']);
    }

    #[Route('/', methods: 'DELETE', name: 'delete')]
    public function delete(): Response
    {
        return $this->json(['message' => 'Resource deleted successfully']);
    }
}
