<?php

namespace App\Controller\Api;

use App\Entity\Carpool;
use App\Entity\Booking;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api')]
class CarpoolApiController extends AbstractController
{
    #[Route('/carpools', name: 'api_carpool_list', methods: ['GET'])]
    public function list(EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $repo = $em->getRepository(Carpool::class);
        $carpools = $repo->findAll();

        $data = $serializer->serialize($carpools, 'json', ['groups' => ['carpool:read']]);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/carpools', name: 'api_carpool_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, LoggerInterface $logger): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $logger->info('Carpool create called', [
            'userId' => $this->getUser()?->getId(),
            'username' => $this->getUser()?->getUserIdentifier() ?? null,
            'requestUri' => $request->getRequestUri()
        ]);

        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $carpool = new Carpool();
            $carpool->setDriver($this->getUser());
            $carpool->setDeparture($data['departure'] ?? '');
            $carpool->setArrival($data['arrival'] ?? '');

            if (!empty($data['departureTime'])) {
                $carpool->setDepartureTime(new \DateTime($data['departureTime']));
            }
            if (!empty($data['arrivalTime'])) {
                $carpool->setArrivalTime(new \DateTime($data['arrivalTime']));
            }

            $carpool->setPrice((float)($data['price'] ?? 0));
            $totalSeats = isset($data['totalSeats']) ? (int)$data['totalSeats'] : 4;
            $carpool->setTotalSeats($totalSeats);
            $carpool->setAvailableSeats($totalSeats);
            $carpool->setStatus($data['status'] ?? 'pending');

            $em->persist($carpool);
            $em->flush();

            return $this->json([
                'id' => $carpool->getId(),
                'message' => 'Trajet créé avec succès'
            ], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            $logger->error('Error creating carpool', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->json(['error' => 'Erreur serveur lors de la création'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/carpools/{id}', name: 'api_carpool_show', methods: ['GET'])]
    public function show(Carpool $carpool, SerializerInterface $serializer): JsonResponse
    {
        $data = $serializer->serialize($carpool, 'json', ['groups' => ['carpool:read']]);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/carpools/{id}', name: 'api_carpool_update', methods: ['PUT'])]
    public function update(Carpool $carpool, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if ($carpool->getDriver() !== $this->getUser()) {
            throw new AccessDeniedException();
        }

        $data = json_decode($request->getContent(), true) ?? [];

        if (isset($data['departure'])) {
            $carpool->setDeparture($data['departure']);
        }
        if (isset($data['arrival'])) {
            $carpool->setArrival($data['arrival']);
        }
        if (!empty($data['departureTime'])) {
            $carpool->setDepartureTime(new \DateTime($data['departureTime']));
        }
        if (!empty($data['arrivalTime'])) {
            $carpool->setArrivalTime(new \DateTime($data['arrivalTime']));
        }
        if (isset($data['price'])) {
            $carpool->setPrice((float)$data['price']);
        }
        if (isset($data['totalSeats'])) {
            $carpool->setTotalSeats((int)$data['totalSeats']);
        }
        if (isset($data['availableSeats'])) {
            $carpool->setAvailableSeats((int)$data['availableSeats']);
        }
        if (isset($data['status'])) {
            $carpool->setStatus($data['status']);
        }

        $em->flush();

        return $this->json(['message' => 'Trajet mis à jour'], Response::HTTP_OK);
    }

    #[Route('/carpools/{id}', name: 'api_carpool_delete', methods: ['DELETE'])]
    public function delete(Carpool $carpool, EntityManagerInterface $em, LoggerInterface $logger): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if ($carpool->getDriver() !== $this->getUser()) {
            throw new AccessDeniedException();
        }

        $id = $carpool->getId();
        $logger->info('Tentative de suppression du carpool', ['id' => $id, 'userId' => $this->getUser()?->getId()]);

        try {
            $bookings = $carpool->getBookings();
            if (is_iterable($bookings)) {
                foreach ($bookings as $booking) {
                    $logger->info('Removing booking', ['bookingId' => $booking->getId()]);
                    $em->remove($booking);
                }
            }

            $em->remove($carpool);
            $em->flush();

            $logger->info('Carpool supprimé avec succès', ['id' => $id]);
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        } catch (\Throwable $e) {
            $logger->error('Erreur lors de la suppression du carpool', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->json(['error' => 'Erreur lors de la suppression', 'detail' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/carpools/{id}/join', name: 'api_carpool_join', methods: ['POST'])]
    public function join(Carpool $carpool, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $data = json_decode($request->getContent(), true) ?? [];
        $seats = isset($data['seats']) ? (int)$data['seats'] : 1;

        if ($seats <= 0 || $seats > $carpool->getAvailableSeats()) {
            return $this->json(['error' => 'Nombre de places invalide'], Response::HTTP_BAD_REQUEST);
        }

        foreach ($carpool->getBookings() as $b) {
            if ($b->getPassenger() === $this->getUser()) {
                return $this->json(['error' => 'Déjà réservé'], Response::HTTP_BAD_REQUEST);
            }
        }

        $booking = new Booking();
        $booking->setPassenger($this->getUser());
        $booking->setCarpool($carpool);
        $booking->setSeats($seats);
        $booking->setStatus(Booking::STATUS_PENDING);

        $em->persist($booking);
        $carpool->setAvailableSeats($carpool->getAvailableSeats() - $seats);
        $em->flush();

        return $this->json([
            'message' => 'Réservation réussie',
            'availableSeats' => $carpool->getAvailableSeats(),
            'bookingId' => $booking->getId()
        ], Response::HTTP_CREATED);
    }

    #[Route('/carpools/{id}/leave', name: 'api_carpool_leave', methods: ['POST'])]
    public function leave(Carpool $carpool, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $booking = null;
        foreach ($carpool->getBookings() as $b) {
            if ($b->getPassenger() === $this->getUser()) {
                $booking = $b;
                break;
            }
        }

        if (!$booking) {
            return $this->json(['error' => 'Pas de réservation trouvée'], Response::HTTP_BAD_REQUEST);
        }

        $seats = $booking->getSeats();
        $em->remove($booking);
        $carpool->setAvailableSeats($carpool->getAvailableSeats() + $seats);
        $em->flush();

        return $this->json([
            'message' => 'Réservation annulée',
            'availableSeats' => $carpool->getAvailableSeats()
        ], Response::HTTP_OK);
    }

    #[Route('/bookings/me', name: 'api_bookings_me', methods: ['GET'])]
    #[Route('/carpools/bookings/me', name: 'api_carpools_bookings_me', methods: ['GET'])]
    public function myBookings(EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $repo = $em->getRepository(Booking::class);
        $bookings = $repo->findBy(['passenger' => $this->getUser()]);

        $data = $serializer->serialize($bookings, 'json', ['groups' => ['booking:read']]);
        return new JsonResponse($data, Response::HTTP_OK, [], true);
    }

    #[Route('/bookings/{id}/status', name: 'api_booking_update_status', methods: ['PATCH', 'POST'])]
    public function updateBookingStatus(Booking $booking, Request $request, EntityManagerInterface $em, LoggerInterface $logger): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        // Autorisation : seul le passager ou le chauffeur du covoiturage peut modifier le statut
        if ($booking->getPassenger() !== $user && $booking->getCarpool()->getDriver() !== $user) {
            throw new AccessDeniedException('Non autorisé à modifier cette réservation.');
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $status = $data['status'] ?? null;
        if (!$status) {
            return $this->json(['error' => 'Missing status'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $logger->info('Updating booking status', [
                'bookingId' => $booking->getId(),
                'requestedStatus' => $status,
                'userId' => $user?->getId()
            ]);

            $booking->setStatus($status);

            // Si ton entity a des timestamps liés aux statuts, mets à jour ici
            if ($status === 'confirmed' && method_exists($booking, 'setConfirmedAt')) {
                $booking->setConfirmedAt(new \DateTime());
            }

            $em->flush();

            return $this->json([
                'id' => $booking->getId(),
                'status' => $booking->getStatus()
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            $logger->error('Erreur updateBookingStatus', [
                'bookingId' => $booking->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->json(['error' => 'Erreur serveur'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}