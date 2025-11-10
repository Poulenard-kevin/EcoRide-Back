<?php

namespace App\Entity;

use App\Repository\CarpoolRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CarpoolRepository::class)]
#[ORM\Table(name: 'covoiturage')] 
class Carpool
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'date_depart', type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date de départ est obligatoire.")]
   
    private ?\DateTimeInterface $departureDate = null;

    #[ORM\Column(name: 'heure_depart', type: Types::TIME_MUTABLE)]
    #[Assert\NotBlank(message: "L'heure de départ est obligatoire.")]
   
    private ?\DateTimeInterface $departureTime = null;

    #[ORM\Column(name: 'lieu_depart', length: 255)]
    #[Assert\NotBlank(message: "Le lieu de départ est obligatoire.")]
    #[Assert\Length(max: 255, maxMessage: "Le lieu de départ ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $departureLocation = null;

    #[ORM\Column(name: 'date_arrivee', type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date d'arrivée est obligatoire.")]
    private ?\DateTimeInterface $arrivalDate = null;

    #[ORM\Column(name: 'heure_arrivee', type: Types::TIME_MUTABLE)]
    #[Assert\NotBlank(message: "L'heure d'arrivée est obligatoire.")]
    private ?\DateTimeInterface $arrivalTime = null;

    #[ORM\Column(name: 'lieu_arrivee', length: 255)] 
    #[Assert\NotBlank(message: "Le lieu d'arrivée est obligatoire.")]
    #[Assert\Length(max: 255, maxMessage: "Le lieu d'arrivée ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $arrivalLocation = null; 

    #[ORM\Column(name: 'prix_par_place')] 
    private ?int $pricePerSeat = null; 

    #[ORM\Column(name: 'nb_places_total')] 
    private ?int $totalSeats = null; 

    #[ORM\Column(name: 'nb_places_dispo')]
    #[Assert\GreaterThanOrEqual(value: 0, message: "Le nombre de places disponibles doit être supérieur ou égal à zéro.")]
    private ?int $availableSeats = null;

    #[ORM\Column(name: "statut", length: 50, nullable: true)] // TODO: rendre NOT NULL après API
    private ?string $status = null;

    // Ajoute aussi les constantes pour les statuts
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_ARCHIVED = 'archived';

    #[ORM\ManyToOne(inversedBy: 'carpools', targetEntity: User::class)]
    #[ORM\JoinColumn(name: "chauffeur_id", nullable: true)] // TODO: rendre NOT NULL après API 
    private ?User $driver = null; 

    #[ORM\OneToMany(mappedBy: 'carpool', targetEntity: Booking::class)]
    private Collection $bookings; 

    #[ORM\ManyToOne(inversedBy: 'carpools')]
    #[ORM\JoinColumn(name: "voiture_id", nullable: true)] // TODO: rendre NOT NULL après API 
    private ?Car $car = null; 

    #[ORM\OneToMany(mappedBy: 'carpool', targetEntity: Review::class)]
    private Collection $reviews; 

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
        $this->reviews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDepartureDate(): ?\DateTimeInterface
    {
        return $this->departureDate;
    }

    public function setDepartureDate(\DateTimeInterface $departureDate): static
    {
        $this->departureDate = $departureDate;

        return $this;
    }

    public function getDepartureTime(): ?\DateTimeInterface
    {
        return $this->departureTime;
    }

    public function setDepartureTime(\DateTimeInterface $departureTime): static
    {
        $this->departureTime = $departureTime;

        return $this;
    }

    public function getDepartureLocation(): ?string
    {
        return $this->departureLocation;
    }

    public function setDepartureLocation(string $departureLocation): static
    {
        $this->departureLocation = $departureLocation;

        return $this;
    }

    public function getArrivalDate(): ?\DateTimeInterface
    {
        return $this->arrivalDate;
    }

    public function setArrivalDate(\DateTimeInterface $arrivalDate): static
    {
        $this->arrivalDate = $arrivalDate;

        return $this;
    }

    public function getArrivalTime(): ?\DateTimeInterface
    {
        return $this->arrivalTime;
    }

    public function setArrivalTime(\DateTimeInterface $arrivalTime): static
    {
        $this->arrivalTime = $arrivalTime;

        return $this;
    }

    public function getArrivalLocation(): ?string
    {
        return $this->arrivalLocation;
    }

    public function setArrivalLocation(string $arrivalLocation): static
    {
        $this->arrivalLocation = $arrivalLocation;

        return $this;
    }

    public function getPricePerSeat(): ?float
    {
        return $this->pricePerSeat;
    }

    public function setPricePerSeat(float $pricePerSeat): static
    {
        $this->pricePerSeat = $pricePerSeat;

        return $this;
    }

    public function getTotalSeats(): ?int
    {
        return $this->totalSeats;
    }

    public function setTotalSeats(int $totalSeats): static
    {
        $this->totalSeats = $totalSeats;

        return $this;
    }

    public function getAvailableSeats(): ?int
    {
        return $this->availableSeats;
    }

    public function setAvailableSeats(int $availableSeats): static
    {
        $this->availableSeats = $availableSeats;

        return $this;
    }

    public function getRemainingSeats(?Booking $excludeBooking = null): int
    {
        $totalReserved = 0;
        foreach ($this->bookings as $booking) {
            if ($excludeBooking && $booking->getId() === $excludeBooking->getId()) {
                continue; // Exclure la réservation en cours
            }
            $totalReserved += $booking->getReservedSeats();
        }

        return max(0, $this->totalSeats - $totalReserved);
    }

    public function getDriver(): ?User
    {
        return $this->driver;
    }

    public function setDriver(?User $driver): static
    {
        $this->driver = $driver;

        return $this;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_ACTIVE => 'Actif',
            self::STATUS_COMPLETED => 'Terminé',
            self::STATUS_CANCELLED => 'Annulé',
            self::STATUS_ARCHIVED => 'Archivé',
        ];
    }

    public function getStatusLabel(): string
    {
        $labels = self::getStatusLabels();
        return $labels[$this->status] ?? 'Statut inconnu';
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setCarpool($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            // set the owning side to null (unless already changed)
            if ($booking->getCarpool() === $this) {
                $booking->setCarpool(null);
            }
        }

        return $this;
    }

    public function getCar(): ?Car
    {
        return $this->car;
    }

    public function setCar(?Car $car): static
    {
        $this->car = $car;

        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setCarpool($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getCarpool() === $this) {
                $review->setCarpool(null);
            }
        }

        return $this;
    }
}
