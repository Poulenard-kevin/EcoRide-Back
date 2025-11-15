<?php
// src/EventSubscriber/ReviewAuthorSubscriber.php
namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities; // si tu as API Platform v2
use App\Entity\Review;
use App\Repository\BookingRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ReviewAuthorSubscriber implements EventSubscriberInterface
{
    private Security $security;
    private BookingRepository $bookingRepository;

    public function __construct(Security $security, BookingRepository $bookingRepository)
    {
        $this->security = $security;
        $this->bookingRepository = $bookingRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['setAuthorAndTarget', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function setAuthorAndTarget(ViewEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request instanceof Request) {
            return;
        }

        // Nous n'intervenons que pour POST sur une Review
        if (Request::METHOD_POST !== $request->getMethod()) {
            return;
        }

        $review = $event->getControllerResult();
        if (!$review instanceof Review) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('Vous devez être connecté pour poster un avis.');
        }

        // Affecte l'auteur si pas déjà renseigné
        if (null === $review->getAuthor()) {
            $review->setAuthor($user);
        }

        // Vérifier la présence du covoiturage et affecter la cible = driver
        $carpool = $review->getCarpool();
        if (!$carpool) {
            throw new BadRequestHttpException('Le covoiturage (carpool) est requis pour laisser un avis.');
        }

        $driver = $carpool->getDriver();
        if (!$driver) {
            throw new BadRequestHttpException('Le covoiturage n\'a pas de conducteur associé.');
        }

        $review->setTarget($driver);

        // Optionnel mais recommandé : vérifier que l'utilisateur a bien une réservation pour ce covoiturage
        $existingBooking = $this->bookingRepository->findOneBy([
            'passenger' => $user,
            'carpool' => $carpool,
            // optionnel : filtrer sur status si nécessaire
        ]);

        if (!$existingBooking) {
            throw new AccessDeniedHttpException('Vous ne pouvez pas noter ce conducteur si vous n\'avez pas réservé ce covoiturage.');
        }
    }
}