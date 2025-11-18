<?php

namespace App\Controller\Api;

use App\Entity\Carpool;
use App\Entity\Booking;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;

#[Route('/api/carpools')]
class CarpoolApiController extends AbstractController
{
    #[Route('', name: 'api_carpool_list', methods: ['GET'])]
    public function list(EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $repo = $em->getRepository(Carpool::class);
        $carpools = $repo->findAll();

        $data = $serializer->serialize($carpools, 'json', ['groups' => ['carpool:list']]);
        return new JsonResponse($data, 200, [], true);
    }

    #[Route('', name: 'api_carpool_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, LoggerInterface $logger): JsonResponse
{
        $logger->info('CarpoolApiController create called', [
            'userId' => $this->getUser()?->getId(),
            'username'     => $this->getUser()?->getUserIdentifier() ?? null,
            'cookieHeader' => $request->headers->get('cookie'),
            'authHeader'   => $request->headers->get('authorization'),
            'requestUri'   => $request->getRequestUri()
        ]);
        
        $this->denyAccessUnlessGranted('ROLE_USER');

        $data = json_decode($request->getContent(), true);

        $carpool = new Carpool();
        $carpool->setDriver($this->getUser());
        $carpool->setDeparture($data['departure'] ?? '');
        $carpool->setArrival($data['arrival'] ?? '');
        $carpool->setDepartureTime(new \DateTime($data['departureTime']));
        $carpool->setArrivalTime(new \DateTime($data['arrivalTime']));
        $carpool->setPrice($data['price'] ?? 0);
        $carpool->setTotalSeats($data['totalSeats'] ?? 4);
        $carpool->setAvailableSeats($data['totalSeats'] ?? 4);
        $carpool->setStatus($data['status'] ?? 'pending');

        $em->persist($carpool);
        $em->flush();

        return $this->json([
            'id' => $carpool->getId(),
            'message' => 'Trajet créé avec succès'
        ], 201);
    }

    #[Route('/{id}', name: 'api_carpool_show', methods: ['GET'])]
    public function show(Carpool $carpool, SerializerInterface $serializer): JsonResponse
    {
        $data = $serializer->serialize($carpool, 'json', ['groups' => ['carpool:detail']]);
        return new JsonResponse($data, 200, [], true);
    }

    #[Route('/{id}', name: 'api_carpool_update', methods: ['PUT'])]
    public function update(Carpool $carpool, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if ($carpool->getDriver() !== $this->getUser()) {
            throw new AccessDeniedException();
        }

        $data = json_decode($request->getContent(), true);

        $carpool->setDeparture($data['departure'] ?? $carpool->getDeparture());
        $carpool->setArrival($data['arrival'] ?? $carpool->getArrival());
        $carpool->setDepartureTime(new \DateTime($data['departureTime']));
        $carpool->setArrivalTime(new \DateTime($data['arrivalTime']));
        $carpool->setPrice($data['price'] ?? $carpool->getPrice());
        $carpool->setTotalSeats($data['totalSeats'] ?? $carpool->getTotalSeats());
        $carpool->setAvailableSeats($data['availableSeats'] ?? $carpool->getAvailableSeats());
        $carpool->setStatus($data['status'] ?? $carpool->getStatus());

        $em->flush();

        return $this->json(['message' => 'Trajet mis à jour']);
    }

    #[Route('/{id}', name: 'api_carpool_delete', methods: ['DELETE'])]
public function delete(Carpool $carpool, EntityManagerInterface $em, LoggerInterface $logger): JsonResponse
{
    $this->denyAccessUnlessGranted('ROLE_USER');
    if ($carpool->getDriver() !== $this->getUser()) {
        throw new AccessDeniedException();
    }

    $id = $carpool->getId();
    $logger->info('Tentative de suppression du carpool (start)', ['id' => $id, 'userId' => $this->getUser()?->getId()]);

    try {
        $bookings = $carpool->getBookings();
        $count = is_iterable($bookings) ? (is_countable($bookings) ? count($bookings) : iterator_count($bookings)) : 0;
        $logger->info('Bookings count before remove', ['id' => $id, 'count' => $count]);

        if ($count > 0) {
            foreach ($bookings as $booking) {
                $logger->info('Removing booking', ['bookingId' => $booking->getId()]);
                $em->remove($booking);
            }
        }

        $logger->info('Removing carpool entity', ['id' => $id]);
        $em->remove($carpool);
        $em->flush();

        $logger->info('Carpool supprimé avec succès', ['id' => $id]);
        return $this->json(null, 204);
    } catch (\Throwable $e) {
        $logger->error('Erreur lors de la suppression du carpool', [
            'id' => $id,
            'error' => $e->getMessage(),
            'class' => get_class($e),
            'trace' => $e->getTraceAsString(),
        ]);

        return $this->json(['error' => 'Erreur lors de la suppression', 'detail' => $e->getMessage()], 500);
    }
}

    #[Route('/{id}/join', name: 'api_carpool_join', methods: ['POST'])]
    public function join(Carpool $carpool, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $data = json_decode($request->getContent(), true);
        $seats = $data['seats'] ?? 1;

        if ($seats <= 0 || $seats > $carpool->getAvailableSeats()) {
            return $this->json(['error' => 'Nombre de places invalide'], 400);
        }

        foreach ($carpool->getBookings() as $b) {
            if ($b->getPassenger() === $this->getUser()) {
                return $this->json(['error' => 'Déjà réservé'], 400);
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
        ], 201);
    }

    #[Route('/{id}/leave', name: 'api_carpool_leave', methods: ['POST'])]
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
            return $this->json(['error' => 'Pas de réservation trouvée'], 400);
        }

        $seats = $booking->getSeats();
        $em->remove($booking);
        $carpool->setAvailableSeats($carpool->getAvailableSeats() + $seats);
        $em->flush();

        return $this->json([
            'message' => 'Réservation annulée',
            'availableSeats' => $carpool->getAvailableSeats(),
        ]);
    }

    #[Route('/bookings/me', name: 'api_bookings_me', methods: ['GET'])]
    public function myBookings(EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $repo = $em->getRepository(Booking::class);
        $bookings = $repo->findBy(['passenger' => $this->getUser()]);

        $data = $serializer->serialize($bookings, 'json', ['groups' => ['booking:detail']]);
        return new JsonResponse($data, 200, [], true);
    }
}