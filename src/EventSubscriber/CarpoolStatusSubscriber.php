<?php
// src/EventSubscriber/CarpoolStatusSubscriber.php
namespace App\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\OnFlushEventArgs;
use App\Entity\Carpool;
use App\Entity\Booking;
use Psr\Log\LoggerInterface;

class CarpoolStatusSubscriber implements EventSubscriber
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getSubscribedEvents(): array
    {
        return [Events::onFlush];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $this->logger->info('CarpoolStatusSubscriber: onFlush called');
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof Carpool) {
                continue;
            }

            $changeSet = $uow->getEntityChangeSet($entity);

            if (!isset($changeSet['status'])) {
                continue;
            }

            $old = $changeSet['status'][0];
            $new = $changeSet['status'][1];

            $this->logger->info('CarpoolStatusSubscriber: detected carpool status change', [
                'carpoolId' => $entity->getId(),
                'old' => $old,
                'new' => $new,
            ]);

            $completedValues = [
                'termine',
                Carpool::STATUS_COMPLETED,
            ];

            if (!in_array($new, $completedValues, true)) {
                continue;
            }

            // Parcourir les réservations et mettre à jour si nécessaire
            foreach ($entity->getBookings() as $booking) {
                // skip si booking null (sécurité)
                if (!$booking instanceof Booking) {
                    continue;
                }

                $current = $booking->getStatus() ?? '';

                // Log immédiat pour voir la valeur exacte
                $this->logger->info('Booking current status', [
                    'bookingId' => $booking->getId(),
                    'status' => $current,
                ]);

                $this->logger->info('Booking before update', [
                    'bookingId' => $booking->getId(),
                    'status' => $current,
                ]);

                if (in_array($current, [Booking::STATUS_PENDING, 'reserve', 'reserved'], true)) {
                    $booking->setStatus(Booking::STATUS_AWAITING_VALIDATION);
                    $this->logger->info('Booking status set to awaiting_validation', [
                        'bookingId' => $booking->getId(),
                        'newStatus' => Booking::STATUS_AWAITING_VALIDATION,
                    ]);

                    $em->persist($booking);
                    $uow->recomputeSingleEntityChangeSet(
                        $em->getClassMetadata(get_class($booking)),
                        $booking
                    );

                    // Log post-recompute pour confirmer que Doctrine prend en compte le changement
                    $this->logger->info('Booking recomputeSingleEntityChangeSet called', [
                        'bookingId' => $booking->getId(),
                    ]);
                }
            }
        }
    }
}