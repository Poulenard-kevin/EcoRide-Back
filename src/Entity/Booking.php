<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\BookingRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\Table(name: 'reservation')] 
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'date_reservation', type: Types::DATETIME_MUTABLE)] 
    private ?DateTimeInterface $bookingDate = null;

    #[Assert\NotNull(message: "Le nombre de places réservées est obligatoire.")]
    #[Assert\Positive(message: "Le nombre de places réservées doit être un entier positif.")]
    #[ORM\Column(name: 'nb_places_reservees')] 
    private ?int $reservedSeats = 1;

    #[ORM\Column(name: "statut", length: 50, nullable: true)] // TODO: rendre NOT NULL après API
    private ?string $status = 'pending'; // Valeur par défaut

    // Constantes de statut
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_PENDING   = 'pending';
    public const STATUS_REFUSED   = 'refused';

    #[ORM\ManyToOne(inversedBy: 'bookings', targetEntity: User::class)]
    #[ORM\JoinColumn(name: "passager_id", nullable: true)] // TODO: rendre NOT NULL après API 
    private ?User $passenger = null; 

    #[ORM\ManyToOne(inversedBy: 'bookings', targetEntity: Carpool::class)]
    #[ORM\JoinColumn(name: "covoiturage_id", nullable: true)] // TODO: rendre NOT NULL après API
    private ?Carpool $carpool = null;

    // Constructeur pour initialiser la date et le statut
    public function __construct()
    {
        $this->bookingDate = new \DateTime();
        $this->status = self::STATUS_PENDING;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBookingDate(): ?DateTimeInterface
    {
        return $this->bookingDate;
    }

    public function setBookingDate(DateTimeInterface $bookingDate): static
    {
        $this->bookingDate = $bookingDate;

        return $this;
    }

    public function getReservedSeats(): ?int
    {
        return (int) $this->reservedSeats;
    }

    public function setReservedSeats(int $reservedSeats): self
    {
        $this->reservedSeats = $reservedSeats ?? 1;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_CONFIRMED => 'Confirmée',
            self::STATUS_CANCELLED => 'Annulée',
            self::STATUS_PENDING   => 'En attente',
            self::STATUS_REFUSED   => 'Refusée',
        ];
    }

    public function getStatusLabel(): string
    {
        $labels = self::getStatusLabels();
        return $labels[$this->status] ?? 'Inconnu';
    }

    public static function getStatusBadgeClasses(): array
    {
        return [
            self::STATUS_CONFIRMED => 'bg-success',
            self::STATUS_CANCELLED => 'bg-danger',
            self::STATUS_PENDING   => 'bg-warning text-dark',
            self::STATUS_REFUSED   => 'bg-danger', // ou bg-secondary si tu veux moins agressif
        ];
    }

    public function getStatusBadgeClass(): string
    {
        $classes = self::getStatusBadgeClasses();
        return $classes[$this->status] ?? 'bg-secondary';
    }

    // Méthodes utiles pour vérifier le statut
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isRefused(): bool
    {
        return $this->status === self::STATUS_REFUSED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    // Vérifier si la réservation peut être annulée
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED]);
    }

    // Vérifier si la réservation peut être confirmée
    public function canBeConfirmed(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    // Vérifier si la réservation peut être refusée
    public function canBeRefused(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getPassenger(): ?User
    {
        return $this->passenger;
    }

    public function setPassenger(?User $passenger): static
    {
        $this->passenger = $passenger;

        return $this;
    }

    public function getCarpool(): ?Carpool
    {
        return $this->carpool;
    }

    public function setCarpool(?Carpool $carpool): static
    {
        $this->carpool = $carpool;

        return $this;
    }

    #[Assert\Callback]
    public function validateReservedSeats(ExecutionContextInterface $context): void
    {
        $carpool = $this->getCarpool();
        if (!$carpool) {
            return;
        }

        // Calculer les places restantes en excluant la réservation en cours
        $remainingSeats = $carpool->getRemainingSeats($this);

        if ($this->reservedSeats > $remainingSeats) {
            $context->buildViolation('Le nombre de places demandées dépasse les places disponibles (' . $remainingSeats . ').')
                ->atPath('reservedSeats')
                ->addViolation();
        }
    }
}