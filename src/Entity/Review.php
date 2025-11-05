<?php

namespace App\Entity;

use App\Repository\ReviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
#[ORM\Table(name: 'avis')] 
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'note', type: 'integer')] 
    private ?int $rating = null; 

    #[ORM\Column(name: 'commentaire', length: 500, nullable: true)] 
    private ?string $comment = null; 

    #[ORM\Column(name: 'date', type: Types::DATETIME_MUTABLE)] 
    private ?\DateTimeInterface $date = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(name: "auteur_id", nullable: true)] // TODO: rendre NOT NULL après API 
    private ?User $author = null; 

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(name: "covoiturage_id", nullable: true)] // TODO: rendre NOT NULL après API 
    private ?Carpool $carpool = null; 

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'receivedReviews')]
    #[ORM\JoinColumn(name: "cible_id", nullable: true)] // TODO: rendre NOT NULL après API
    private ?User $target = null; 

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

    public function __construct()
    {
        $this->date = new \DateTime();
    }
}
