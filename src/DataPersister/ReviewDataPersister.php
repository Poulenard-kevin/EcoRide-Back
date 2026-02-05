<?php
// src/DataPersister/ReviewDataPersister.php
namespace App\DataPersister;

use ApiPlatform\Core\DataPersister\ContextAwareDataPersisterInterface;
use App\Entity\Review;
use App\Entity\Carpool;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\LockMode;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Security;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ReviewDataPersister implements ContextAwareDataPersisterInterface
{
    private EntityManagerInterface $em;
    private Security $security;
    private BookingRepository $bookingRepo;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $em,
        Security $security,
        BookingRepository $bookingRepo,
        LoggerInterface $logger
    ) {
        $this->em = $em;
        $this->security = $security;
        $this->bookingRepo = $bookingRepo;
        $this->logger = $logger;
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

        $booking = $data->getBooking();
        if (!$booking) {
            throw new BadRequestHttpException('Réservation introuvable. Veuillez fournir "booking": "/api/bookings/{id}".');
        }

        // Vérifier que l'utilisateur a le droit de noter (la réservation doit appartenir au passager)
        $passenger = null;
        if (method_exists($booking, 'getPassenger')) {
            $passenger = $booking->getPassenger();
        } elseif (method_exists($booking, 'getUser')) {
            $passenger = $booking->getUser();
        }

        if ($passenger === null || $passenger->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas autorisé(e) à noter cette réservation.');
        }

        // Empêcher double review (optionnel)
        $existing = $this->em->getRepository(Review::class)
            ->findOneBy(['booking' => $booking]);
        if ($existing) {
            throw new BadRequestHttpException('Cette réservation a déjà été notée.');
        }

        $data->setAuthor($user);

        // Persist initial de la review
        $this->em->persist($data);
        $this->em->flush();

        // --- Maintenant : mettre à jour état booking + potentiellement clore le carpool ---
        $conn = $this->em->getConnection();
        $conn->beginTransaction();
        try {
            // Recharger les entités sous un verrou afin d'éviter races
            $this->em->refresh($booking); // utile si booking a été modifié ailleurs
            $carpool = null;
            if (method_exists($booking, 'getCarpool')) {
                $carpool = $booking->getCarpool();
            }

            if ($carpool) {
                // Recharger et locker le carpool
                $carpoolRepo = $this->em->getRepository(Carpool::class);
                $carpool = $carpoolRepo->find($carpool->getId());
                if ($carpool) {
                    $this->em->lock($carpool, LockMode::PESSIMISTIC_WRITE);
                }

                // 1) Marquer la réservation comme validée (si ton modèle le prévoit)
                if (method_exists($booking, 'setStatus')) {
                    $booking->setStatus('validated');
                } elseif (method_exists($booking, 'setStatut')) {
                    $booking->setStatut('valide');
                }
                $this->em->persist($booking);
                $this->em->flush();

                // 2) Récupérer toutes les réservations liées au carpool
                $bookings = $this->bookingRepo->findBy(['carpool' => $carpool]);

                // Détermine si TOUTES les réservations sont validées
                $acceptedStatuses = ['validated', 'valide', 'confirmed', 'completed', 'approved'];
                $allValidated = true;
                foreach ($bookings as $b) {
                    $rawStatus = null;
                    if (method_exists($b, 'getStatus')) {
                        $rawStatus = (string) $b->getStatus();
                    } elseif (method_exists($b, 'getStatut')) {
                        $rawStatus = (string) $b->getStatut();
                    } else {
                        $rawStatus = '';
                    }
                    $st = mb_strtolower(trim($rawStatus));
                    if ($st === '') {
                        $allValidated = false;
                        break;
                    }
                    // rejette annulations
                    if (mb_strpos($st, 'annul') !== false || mb_strpos($st, 'cancel') !== false) {
                        $allValidated = false;
                        break;
                    }
                    if (!in_array($st, $acceptedStatuses, true)) {
                        $allValidated = false;
                        break;
                    }
                }

                // 3) Si tout validé -> marquer carpool completed
                if ($allValidated) {
                    // adapte les noms de méthode suivant ton entité Carpool
                    if (method_exists($carpool, 'setStatus')) {
                        $carpool->setStatus('completed');
                    } elseif (method_exists($carpool, 'setStatut')) {
                        $carpool->setStatut('completed'); // ou 'termine' selon ta nomenclature
                    }
                    $this->em->persist($carpool);
                    $this->em->flush();

                    $this->logger->info('Carpool closed automatically after last review', [
                        'carpoolId' => $carpool->getId(),
                        'triggeredByBooking' => $booking->getId(),
                    ]);
                } else {
                    $this->logger->info('Carpool not closed (not all bookings validated)', [
                        'carpoolId' => $carpool->getId(),
                        'triggeredByBooking' => $booking->getId(),
                    ]);
                }
            }

            $conn->commit();
        } catch (\Throwable $e) {
            try {
                $conn->rollBack();
            } catch (\Throwable $rb) {
                $this->logger->error('Rollback failed while processing review persist: '.$rb->getMessage(), ['exception' => $rb]);
            }
            $this->logger->error('Error while post-processing review: '.$e->getMessage(), ['exception' => $e]);
            // Ne pas masquer l'erreur métier déjà connue
            throw new HttpException(500, 'Erreur lors du traitement de la review (post‑processing).', $e);
        }

        return $data;
    }

    public function remove($data, array $context = [])
    {
        $this->em->remove($data);
        $this->em->flush();
    }
}