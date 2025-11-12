<?php

namespace App\Entity;

use App\Repository\ReviewRepository;
use Doctrine\DBAL\Types\Types;
use App\Entity\Booking;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
#[ORM\Table(name: 'avis')]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'note', type: 'integer')]
    #[Assert\NotNull(message: "La note est requise.")]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'La note doit être entre {{ min }} et {{ max }}.')]
    private ?int $rating = null;

    #[ORM\Column(name: 'commentaire', length: 500, nullable: true)]
    #[Assert\Length(max: 500, maxMessage: "Le commentaire ne peut dépasser {{ limit }} caractères.")]
    private ?string $comment = null;

    #[ORM\Column(name: 'date', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(name: "auteur_id", nullable: false, onDelete: "CASCADE")]
    #[Assert\NotNull(message: "L'auteur est requis.")]
    private ?User $author = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(name: "covoiturage_id", nullable: false, onDelete: "CASCADE")]
    #[Assert\NotNull(message: "Le covoiturage est requis.")]
    private ?Carpool $carpool = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'receivedReviews')]
    #[ORM\JoinColumn(name: "cible_id", nullable: false, onDelete: "CASCADE")]
    #[Assert\NotNull(message: "La cible est requise.")]
    private ?User $target = null;

    #[ORM\Column(name: 'valide', type: 'boolean')]
    private bool $validated = false;

    #[ORM\ManyToOne(targetEntity: Booking::class, inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Booking $booking = null;

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';

    #[ORM\Column(type: 'string', length: 16)]
    private string $status = self::STATUS_PENDING;


    public function __construct()
    {
        $this->date = new \DateTime();
        $this->validated = false;
    }

    // --- getters / setters ---

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
}