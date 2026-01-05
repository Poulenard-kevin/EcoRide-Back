<?php
namespace App\DataPersister;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use App\Entity\Review;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Security;

class ReviewDataPersister implements ContextAwareDataPersisterInterface
{
    private EntityManagerInterface $em;
    private Security $security;
    private BookingRepository $bookingRepo;

    public function __construct(EntityManagerInterface $em, Security $security, BookingRepository $bookingRepo)
    {
        $this->em = $em;
        $this->security = $security;
        $this->bookingRepo = $bookingRepo;
    }

    public function supports($data, array $context = []): bool
    {
        return $data instanceof Review;
    }

    public function persist($data, array $context = [])
    {
        /** @var Review $data */
        $user = $this->security->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('Utilisateur non authentifié.');
        }

        // Si booking est fourni comme IRI, API Platform aura résolu l'objet Booking
        $booking = $data->getBooking();
        if (!$booking) {
            throw new BadRequestHttpException('Réservation introuvable. Veuillez fournir "booking": "/api/bookings/{id}".');
        }

        // Vérifier que l'utilisateur a le droit de noter (la réservation doit appartenir au passager)
        // Ajuste la méthode selon ton Booking entity (ex: getPassenger() / getUser())
        if (method_exists($booking, 'getPassenger')) {
            $passenger = $booking->getPassenger();
        } elseif (method_exists($booking, 'getUser')) {
            $passenger = $booking->getUser();
        } else {
            $passenger = null;
        }

        if ($passenger === null || $passenger->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé(e) à noter cette réservation.');
        }

        // Prévenir double-review pour la même réservation (optionnel)
        $existing = $this->em->getRepository(Review::class)
            ->findOneBy(['booking' => $booking]);
        if ($existing) {
            throw new BadRequestHttpException('Cette réservation a déjà été notée.');
        }

        // Remplir automatiquement les champs liés
        $data->setAuthor($user);

        // Récupérer le covoiturage et le conducteur depuis la réservation
        if (method_exists($booking, 'getCarpool')) {
            $carpool = $booking->getCarpool();
            if ($carpool) {
                $data->setCarpool($carpool);
                // suppose que Carpool::getDriver() existe
                if (method_exists($carpool, 'getDriver')) {
                    $data->setTarget($carpool->getDriver());
                }
            }
        }

        // Persist
        $this->em->persist($data);
        $this->em->flush();

        return $data;
    }

    public function remove($data, array $context = [])
    {
        $this->em->remove($data);
        $this->em->flush();
    }
}