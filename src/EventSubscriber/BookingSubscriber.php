<?php
// src/EventSubscriber/BookingSubscriber.php
namespace App\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use App\Entity\Booking;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BookingSubscriber implements EventSubscriberInterface
{
    public function __construct(private Security $security) {}

    public static function getSubscribedEvents(): array
    {
        // PRE_VALIDATE pour intervenir avant la validation API Platform
        return [
            KernelEvents::VIEW => ['onKernelView', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function onKernelView(ViewEvent $event): void
    {
        $request = $event->getRequest();
        $method = $request->getMethod();

        // Nous ne voulons agir que sur POST de Booking
        if ('POST' !== $method) return;

        $booking = $event->getControllerResult();
        if (!$booking instanceof Booking) return;

        $user = $this->security->getUser();
        if (!$user) {
            // Pas d'utilisateur connecté : refuser
            throw new AccessDeniedHttpException('Authentication required to create a booking.');
        }

        // Si passenger déjà fourni, vérifier qu'il correspond à l'user (optionnel)
        if (null === $booking->getPassenger()) {
            $booking->setPassenger($user);
        } else {
            // Eviter réservation pour un autre utilisateur par injection côté client
            if ($booking->getPassenger()->getId() !== $user->getId() && !in_array('ROLE_ADMIN', $user->getRoles() ?? [], true)) {
                throw new BadRequestHttpException('Vous ne pouvez pas réserver au nom d\'un autre utilisateur.');
            }
        }

        // Si besoin tu peux aussi vérifier que carpool est bien renseigné ici (mais ça peut rester dans DataPersister)
    }
}