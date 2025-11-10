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
    private ?int $reservedSeats = null;

    #[ORM\Column(name: "statut", length: 50, nullable: true)] // TODO: rendre NOT NULL après API
    private ?string $status = null; 

    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_PENDING = 'pending';

    #[ORM\ManyToOne(inversedBy: 'bookings', targetEntity: User::class)]
    #[ORM\JoinColumn(name: "passager_id", nullable: true)] // TODO: rendre NOT NULL après API 
    private ?User $passenger = null; 

    #[ORM\ManyToOne(inversedBy: 'bookings', targetEntity: Carpool::class)]
    #[ORM\JoinColumn(name: "covoiturage_id", nullable: true)] // TODO: rendre NOT NULL après API
    private ?Carpool $carpool = null; 

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
        return $this->reservedSeats;
    }

    public function setReservedSeats(int $reservedSeats): static
    {
        $this->reservedSeats = $reservedSeats;

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
            self::STATUS_PENDING => 'En attente',
        ];
    }

    public function getStatusLabel(): string
    {
        $labels = self::getStatusLabels();
        return $labels[$this->status] ?? 'Statut inconnu';
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