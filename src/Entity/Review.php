<?php

namespace App\Entity;

use App\Repository\ReviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=ReviewRepository::class)
 * @ORM\Table(name="avis")
 * @ApiResource(
 *     normalizationContext={"groups"={"review:read"}},
 *     denormalizationContext={"groups"={"review:write"}},
 *     collectionOperations={
 *         "get"={"security"="is_granted('ROLE_USER')"},
 *         "post"={"security"="is_granted('ROLE_USER')"}
 *     },
 *     itemOperations={
 *         "get"={"security"="is_granted('ROLE_USER')"},
 *         "put"={"security"="object.getAuthor() == user or is_granted('ROLE_ADMIN')"},
 *         "delete"={"security"="is_granted('ROLE_ADMIN')"}
 *     }
 * )
 */
class Review
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"review:read"})
     */
    private ?int $id = null;

    /**
     * @ORM\Column(name="note", type="integer")
     * @Assert\NotNull(message="La note est requise.")
     * @Assert\Range(min=1, max=5, notInRangeMessage="La note doit être entre {{ min }} et {{ max }}.")
     * @Groups({"review:read", "review:write"})
     */
    private ?int $rating = null;

    /**
     * @ORM\Column(name="commentaire", type="string", length=500, nullable=true)
     * @Assert\Length(max=500, maxMessage="Le commentaire ne peut dépasser {{ limit }} caractères.")
     * @Groups({"review:read", "review:write"})
     */
    private ?string $comment = null;

    /**
     * @ORM\Column(name="date", type="datetime")
     * @Groups({"review:read"})
     */
    private ?\DateTimeInterface $date = null;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="reviews")
     * @ORM\JoinColumn(name="auteur_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @Assert\NotNull(message="L'auteur est requis.")
     * @Groups({"review:read"})
     */
    private ?User $author = null;

    /**
     * @ORM\ManyToOne(targetEntity=Carpool::class, inversedBy="reviews")
     * @ORM\JoinColumn(name="covoiturage_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @Assert\NotNull(message="Le covoiturage est requis.")
     * @Groups({"review:read", "review:write"})
     */
    private ?Carpool $carpool = null;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="receivedReviews")
     * @ORM\JoinColumn(name="cible_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @Assert\NotNull(message="La cible est requise.")
     * @Groups({"review:read"})
     */
    private ?User $target = null;

    /**
     * @ORM\Column(name="valide", type="boolean")
     * @Groups({"review:read"})
     */
    private bool $validated = false;

    /**
     * @ORM\ManyToOne(targetEntity=Booking::class, inversedBy="reviews")
     * @ORM\JoinColumn(onDelete="SET NULL", nullable=true)
     * @Groups({"review:read"})
     */
    private ?Booking $booking = null;

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';

    /**
     * @ORM\Column(type="string", length=16)
     * @Groups({"review:read"})
     */
    private string $status = self::STATUS_PENDING;

    public function __construct()
    {
        $this->date = new \DateTime();
        $this->validated = false;
        $this->status = self::STATUS_PENDING;
    }

    // --- Getters / Setters ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;
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

    public function getTarget(): ?User
    {
        return $this->target;
    }

    public function setTarget(?User $target): static
    {
        $this->target = $target;
        return $this;
    }

    public function getDriver(): ?User
    {
        return $this->target;
    }

    public function setDriver(?User $driver): static
    {
        $this->target = $driver;
        return $this;
    }

    public function isValidated(): bool
    {
        return $this->validated;
    }

    public function setValidated(bool $validated): static
    {
        $this->validated = $validated;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getBooking(): ?Booking
    {
        return $this->booking;
    }

    public function setBooking(?Booking $booking): self
    {
        $this->booking = $booking;
        return $this;
    }
}