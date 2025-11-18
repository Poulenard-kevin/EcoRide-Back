<?php

namespace App\Entity;

use Symfony\Component\Serializer\Annotation\SerializedName;
use App\Repository\CarpoolRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=CarpoolRepository::class)
 * @ORM\Table(name="covoiturage")
 * @ApiResource(
 *     normalizationContext={"groups"={"carpool:read"}},
 *     denormalizationContext={"groups"={"carpool:write"}},
 *     collectionOperations={
 *         "get"={
 *           "path"="/carpools",
 *           "security"="is_granted('ROLE_USER')",
 *           "openapi_context"={
 *             "summary"="Récupère la liste des covoiturages",
 *             "description"="Retourne la liste paginée des covoiturages actifs. Accessible à tous les utilisateurs connectés.",
 *             "responses"={
 *               "200"={"description"="Liste des covoiturages"}
 *             }
 *           }
 *         },
 *         "post"={
 *           "path"="/carpools",
 *           "security"="is_granted('ROLE_USER') or is_granted('ROLE_ADMIN')",
 *           "openapi_context"={
 *             "summary"="Propose un nouveau covoiturage",
 *             "description"="Crée un covoiturage. Le conducteur est automatiquement défini comme l'utilisateur courant. Le véhicule doit être sélectionné parmi ceux de l'utilisateur.",
 *             "responses"={
 *               "201"={"description"="Covoiturage proposé"},
 *               "400"={"description"="Erreurs de validation"}
 *             }
 *           }
 *         }
 *     },
 *     itemOperations={
 *         "get"={
 *           "path"="/carpools/{id}",
 *           "security"="is_granted('ROLE_USER')",
 *           "openapi_context"={
 *             "summary"="Récupère un covoiturage",
 *             "description"="Affiche les détails d’un covoiturage. Accessible à tous les utilisateurs connectés.",
 *             "responses"={
 *               "200"={"description"="Détails du covoiturage"},
 *               "404"={"description"="Covoiturage introuvable"}
 *             }
 *           }
 *         },
 *         "put"={
 *           "path"="/carpools/{id}",
 *           "security"="object.getDriver() == user or is_granted('ROLE_ADMIN')",
 *           "openapi_context"={
 *             "summary"="Modifie un covoiturage",
 *             "description"="Permet au conducteur ou à un admin de modifier les informations du covoiturage. Certaines modifications peuvent être bloquées selon le statut (ex: après démarrage).",
 *             "responses"={
 *               "200"={"description"="Covoiturage mis à jour"},
 *               "403"={"description"="Accès refusé"}
 *             }
 *           }
 *         },
 *         "patch"={
 *           "path"="/carpools/{id}",
 *           "security"="object.getDriver() == user or is_granted('ROLE_ADMIN')",
 *           "openapi_context"={
 *             "summary"="Modifier partiellement un covoiturage",
 *             "description"="Permet au conducteur ou à un admin de modifier partiellement les informations du covoiturage. Certaines modifications peuvent être bloquées selon le statut (ex: après démarrage).",
 *             "responses"={ 
 *               "200"={"description"="Covoiturage mis à jour"} 
 *             }
 *           }
 *         },
 *         "delete"={
 *           "path"="/carpools/{id}",
 *           "security"="object.getDriver() == user or is_granted('ROLE_ADMIN')",
 *           "openapi_context"={
 *             "summary"="Supprime un covoiturage",
 *             "description"="Permet au conducteur du covoiturage ou à un administrateur de supprimer le covoiturage.",
 *             "responses"={
 *               "204"={"description"="Covoiturage supprimé"}
 *             }
 *           }
 *         }
 *     }
 * )
 */
class Carpool
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"carpool:read"})
     */
    private ?int $id = null;

    /**
     * @ORM\Column(name="date_depart", type="date")
     * @Assert\NotBlank(message="La date de départ est obligatoire.")
     * @Groups({"carpool:read", "carpool:write"})
     */
    private ?\DateTimeInterface $departureDate = null;

    /**
     * @ORM\Column(name="heure_depart", type="time")
     * @Assert\NotBlank(message="L'heure de départ est obligatoire.")
     * @Groups({"carpool:read", "carpool:write"})
     */
    private ?\DateTimeInterface $departureTime = null;

    /**
     * @ORM\Column(name="lieu_depart", type="string", length=255)
     * @Assert\NotBlank(message="Le lieu de départ est obligatoire.")
     * @Assert\Length(max=255, maxMessage="Le lieu de départ ne peut pas dépasser {{ limit }} caractères.")
     * @Groups({"carpool:read", "carpool:write"})
     */
    private ?string $departureLocation = null;

    /**
     * @ORM\Column(name="date_arrivee", type="date")
     * @Assert\NotBlank(message="La date d'arrivée est obligatoire.")
     * @Groups({"carpool:read", "carpool:write"})
     */
    private ?\DateTimeInterface $arrivalDate = null;

    /**
     * @ORM\Column(name="heure_arrivee", type="time")
     * @Assert\NotBlank(message="L'heure d'arrivée est obligatoire.")
     * @Groups({"carpool:read", "carpool:write"})
     */
    private ?\DateTimeInterface $arrivalTime = null;

    /**
     * @ORM\Column(name="lieu_arrivee", type="string", length=255)
     * @Assert\NotBlank(message="Le lieu d'arrivée est obligatoire.")
     * @Assert\Length(max=255, maxMessage="Le lieu d'arrivée ne peut pas dépasser {{ limit }} caractères.")
     * @Groups({"carpool:read", "carpool:write"})
     */
    private ?string $arrivalLocation = null;

    /**
     * @ORM\Column(name="prix_par_place", type="float", nullable=true)
     * @Groups({"carpool:read", "carpool:write"})
     */
    private ?float $pricePerSeat = null;

    /**
     * @ORM\Column(name="nb_places_total", type="integer")
     * @Groups({"carpool:read"})
     * @Symfony\Component\Serializer\Annotation\SerializedName("nbPlacesTotal")
     * @Assert\Positive(message="Le nombre total de places doit être positif.")
     */
    private ?int $totalSeats = null;

    /**
     * @ORM\Column(name="nb_places_dispo", type="integer")
     * @Groups({"carpool:read"})   
     * @Assert\GreaterThanOrEqual(value=0, message="Le nombre de places disponibles doit être supérieur ou égal à zéro.")
     */
    private ?int $availableSeats = null;

    /**
     * @ORM\Column(name="statut", type="string", length=50, nullable=true)
     * @Groups({"carpool:read", "carpool:write"})
     */
    private ?string $status = null;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_STARTED = 'started';
    public const STATUS_ONGOING = 'ongoing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_ARCHIVED = 'archived';

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="carpools")
     * @ORM\JoinColumn(name="chauffeur_id", referencedColumnName="id", nullable=true)
     * @Groups({"carpool:read", "carpool:write", "user:read"})
     */
    private ?User $driver = null;

    /**
     * @ORM\OneToMany(targetEntity=Booking::class, mappedBy="carpool", orphanRemoval=true, cascade={"remove"})
     */
    private Collection $bookings;

    /**
     * @ORM\ManyToOne(targetEntity=Car::class, inversedBy="carpools")
     * @ORM\JoinColumn(name="voiture_id", referencedColumnName="id", nullable=true)
     * @Groups({"carpool:read", "carpool:write", "car:read"})
     */
    private ?Car $car = null;

    /**
     * @ORM\OneToMany(targetEntity=Review::class, mappedBy="carpool")
     */
    private Collection $reviews;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"carpool:read"})
     */
    private ?\DateTimeInterface $archivedAt = null;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
        $this->reviews = new ArrayCollection();
    }

    // --- Getters / Setters ---

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

    public function setPricePerSeat(?float $pricePerSeat): static
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

    /**
     * Calcule le nombre de places restantes en tenant compte des réservations.
     * Si $excludeBooking est fourni, on l'exclut du calcul (utile lors de la modification).
     */
    public function getRemainingSeats(?Booking $excludeBooking = null): int
    {
        $totalReserved = 0;
        foreach ($this->bookings as $booking) {
            if ($excludeBooking && $booking->getId() === $excludeBooking->getId()) {
                continue;
            }
            $totalReserved += (int) $booking->getReservedSeats();
        }

        $total = (int) ($this->totalSeats ?? 0);
        return max(0, $total - $totalReserved);
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

    public function setStatus(?string $status): self
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
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_ACTIVE => 'Actif',
            self::STATUS_STARTED => 'Démarré',
            self::STATUS_ONGOING => 'En cours',
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

    public static function getStatusBadgeClasses(): array
    {
        return [
            self::STATUS_DRAFT    => 'bg-light text-dark',
            self::STATUS_ACTIVE   => 'bg-success',
            self::STATUS_STARTED  => 'bg-info text-dark',
            self::STATUS_ONGOING  => 'bg-info text-dark',
            self::STATUS_COMPLETED=> 'bg-primary text-white',
            self::STATUS_CANCELLED=> 'bg-danger',
            self::STATUS_ARCHIVED => 'bg-secondary',
        ];
    }

    public function getStatusBadgeClass(): string
    {
        $classes = self::getStatusBadgeClasses();
        return $classes[$this->status] ?? 'bg-light text-dark';
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
            if ($review->getCarpool() === $this) {
                $review->setCarpool(null);
            }
        }
        return $this;
    }

    public function getArchivedAt(): ?\DateTimeInterface
    {
        return $this->archivedAt;
    }

    public function setArchivedAt(?\DateTimeInterface $archivedAt): self
    {
        $this->archivedAt = $archivedAt;
        return $this;
    }
}