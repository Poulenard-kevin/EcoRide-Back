<?php

namespace App\Entity;

use DateTimeInterface;
use App\Repository\BookingRepository;
use App\Entity\Review;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @ORM\Entity(repositoryClass=BookingRepository::class)
 * @ORM\Table(name="reservation")
 * @ApiResource(
 *     normalizationContext={"groups"={"booking:read"}},
 *     denormalizationContext={"groups"={"booking:write"}},
 *     collectionOperations={
 *         "get"={
 *           "path"="/bookings",
 *           "security"="is_granted('ROLE_USER')",
 *           "openapi_context"={
 *             "summary"="Récupère la liste des réservations",
 *             "description"="Retourne la liste paginée des réservations. Accessible aux utilisateurs connectés.",
 *             "responses"={
 *               "200"={"description"="Liste des réservations"}
 *             }
 *           }
 *         },
 *         "post"={
 *           "path"="/bookings",
 *           "security"="is_granted('ROLE_USER')",
 *           "openapi_context"={
 *             "summary"="Crée une réservation",
 *             "description"="Permet à un utilisateur connecté de réserver des places pour un covoiturage. Le passager (`passenger`) est défini automatiquement côté serveur à partir de l'utilisateur authentifié : ne pas l'envoyer dans le payload.",
 *             "responses"={
 *               "201"={"description"="Réservation créée"},
 *               "400"={"description"="Erreurs de validation (ex: places insuffisantes)"},
 *               "403"={"description"="Non autorisé"}
 *             }
 *           }
 *         }
 *     },
 *     itemOperations={
 *         "get"={
 *           "path"="/bookings/{id}",
 *           "security"="is_granted('ROLE_USER')",
 *           "openapi_context"={
 *             "summary"="Récupère une réservation",
 *             "description"="Affiche les détails d'une réservation. Accessible aux utilisateurs connectés (à adapter si tu veux restreindre aux propriétaires/admin).",
 *             "responses"={
 *               "200"={"description"="Détails de la réservation"},
 *               "404"={"description"="Réservation introuvable"}
 *             }
 *           }
 *         },
 *         "put"={
 *           "path"="/bookings/{id}",
 *           "security"="object.getPassenger() == user or is_granted('ROLE_ADMIN')",
 *           "openapi_context"={
 *             "summary"="Met à jour une réservation",
 *             "description"="Permet au passager (propriétaire de la réservation) ou à un admin de modifier une réservation (ex : nombre de places si le covoiturage le permet). Certains changements peuvent être refusés par la validation métier (places restantes).",
 *             "responses"={
 *               "200"={"description"="Réservation mise à jour"},
 *               "400"={"description"="Erreurs de validation"},
 *               "403"={"description"="Accès refusé"}
 *             }
 *           }
 *         },
 *         "delete"={
 *           "path"="/bookings/{id}",
 *           "security"="object.getPassenger() == user or is_granted('ROLE_ADMIN')",
 *           "openapi_context"={
 *             "summary"="Supprime une réservation",
 *             "description"="Permet au passager (propriétaire de la réservation) ou à un administrateur de supprimer une réservation.",
 *             "responses"={
 *               "204"={"description"="Réservation supprimée"}
 *             }
 *           }
 *         }
 *     }
 * )
 */
class Booking
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"booking:read"})
     */
    private ?int $id = null;

    /**
     * @ORM\Column(name="date_reservation", type="datetime")
     * @Groups({"booking:read"})
     */
    private ?DateTimeInterface $bookingDate = null;

    /**
     * @Assert\NotNull(message="Le nombre de places réservées est obligatoire.")
     * @Assert\Positive(message="Le nombre de places réservées doit être un entier positif.")
     * @ORM\Column(name="nb_places_reservees", type="integer")
     * @Groups({"booking:read", "booking:write"})
     * @SerializedName("seats") // optionnel : mappe le nom JSON "seats" sur cette propriété
     */
    private ?int $reservedSeats = 1;

    /**
     * @ORM\Column(name="statut", type="string", length=50, nullable=true)
     * @Groups({"booking:read"})
     */
    private ?string $status = self::STATUS_PENDING;

    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_PENDING   = 'pending';
    public const STATUS_REFUSED   = 'refused';
    public const STATUS_AWAITING_VALIDATION = 'awaiting_validation';
    public const STATUS_COMPLETED = 'completed';

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="bookings")
     * @ORM\JoinColumn(name="passager_id", referencedColumnName="id", nullable=false)
     * @Groups({"booking:read", "booking:write", "user:read"})
     * @Assert\NotNull(message="Le passager est obligatoire.")
     */
    private ?User $passenger = null;

    /**
     * @ORM\ManyToOne(targetEntity=Carpool::class, inversedBy="bookings")
     * @ORM\JoinColumn(name="covoiturage_id", referencedColumnName="id", nullable=true)
     * @Groups({"booking:read", "booking:write", "carpool:read"})
     */
    private ?Carpool $carpool = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"booking:read"})
     */
    private ?\DateTimeInterface $completedAt = null;

    /**
     * @ORM\OneToMany(targetEntity=Review::class, mappedBy="booking", cascade={"remove"})
     * @Groups({"booking:read"})
     */
    private Collection $reviews;

    public function __construct()
    {
        $this->bookingDate = new \DateTime();
        $this->reviews = new ArrayCollection();
        $this->status = self::STATUS_PENDING;
        $this->reservedSeats = 1;
    }

    // --- Getters / Setters ---

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

    public function getSeats(): ?int
    {
        return $this->reservedSeats;
    }

    public function setSeats(int $seats): self
    {
        $this->reservedSeats = $seats;
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
            self::STATUS_COMPLETED => 'Terminée',
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
            self::STATUS_REFUSED   => 'bg-danger',
            self::STATUS_COMPLETED => 'bg-primary text-white',
        ];
    }

    public function getStatusBadgeClass(): string
    {
        $classes = self::getStatusBadgeClasses();
        return $classes[$this->status] ?? 'bg-secondary';
    }

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

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED], true);
    }

    public function canBeConfirmed(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeRefused(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getPassenger(): ?User
    {
        return $this->passenger;
    }

    public function setPassenger(?User $passenger): self
    {
        $this->passenger = $passenger;
        return $this;
    }

    /**
     * Alias getUser pour compatibilité avec ton code/Twig existant
     */
    public function getUser(): ?User
    {
        return $this->getPassenger();
    }

    /**
     * Alias setUser pour compatibilité
     */
    public function setUser(?User $user): self
    {
        return $this->setPassenger($user);
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

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeInterface $completedAt): self
    {
        $this->completedAt = $completedAt;
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
            $review->setBooking($this);
        }
        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            if ($review->getBooking() === $this) {
                $review->setBooking(null);
            }
        }
        return $this;
    }

    /**
     * Validation personnalisée : s'assure que reservedSeats <= places restantes du covoiturage.
     *
     * @Assert\Callback
     */
    public function validateReservedSeats(ExecutionContextInterface $context): void
    {
        $carpool = $this->getCarpool();
        if (!$carpool) {
            // Pas de carpool renseigné : une autre validation peut s'en charger
            return;
        }

        // Calculer les places restantes en excluant la réservation en cours
        if (!method_exists($carpool, 'getRemainingSeats')) {
            return;
        }

        $remainingSeats = $carpool->getRemainingSeats($this);

        if ($this->reservedSeats > $remainingSeats) {
            $context->buildViolation('Le nombre de places demandées dépasse les places disponibles (' . $remainingSeats . ').')
                ->atPath('reservedSeats')
                ->addViolation();
        }
    }
}