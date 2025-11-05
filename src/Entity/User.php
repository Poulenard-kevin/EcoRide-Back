<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'utilisateur')] 
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'nom', length: 100)] 
    private ?string $lastName = null;

    #[ORM\Column(name: 'prenom', length: 100)] 
    private ?string $firstName = null;

    #[ORM\Column(name: 'email', length: 180)]
    private ?string $email = null;

    #[ORM\Column(name: 'mot_de_passe', length: 255)] 
    private ?string $password = null;

    public const ROLE_VISITEUR = 'ROLE_VISITEUR';
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_EMPLOYE = 'ROLE_EMPLOYE';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    #[ORM\Column(name: 'role', length: 50)]
    private ?string $role = null;

    #[ORM\Column(name: 'note_moyenne', nullable: true)] 
    private ?float $averageRating = null;

    #[ORM\Column(name: 'a_propos', type: Types::TEXT, nullable: true)] 
    private ?string $about = null;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Car::class, orphanRemoval: true)]
    #[ORM\JoinColumn(name: 'voitures', referencedColumnName: 'id')] 
    private Collection $cars;

    #[ORM\OneToMany(mappedBy: 'driver', targetEntity: Carpool::class)]
    #[ORM\JoinColumn(name: 'covoiturages_proposes', referencedColumnName: 'id')] 
    private Collection $carpools;

    #[ORM\OneToMany(mappedBy: 'passenger', targetEntity: Booking::class)]
    #[ORM\JoinColumn(name: 'reservations', referencedColumnName: 'id')] 
    private Collection $bookings;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Review::class, orphanRemoval: true)]
    #[ORM\JoinColumn(name: 'avis', referencedColumnName: 'id')] 
    private Collection $reviews;

    #[ORM\OneToMany(mappedBy: 'target', targetEntity: Review::class, orphanRemoval: true)]
    #[ORM\JoinColumn(name: 'avis_recus', referencedColumnName: 'id')] 
    private Collection $receivedReviews;

    public function __construct()
    {
        $this->receivedReviews = new ArrayCollection();
        $this->cars = new ArrayCollection();
        $this->carpools = new ArrayCollection();
        $this->bookings = new ArrayCollection();
        $this->reviews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isEmployee(): bool
    {
        return $this->role === self::ROLE_EMPLOYE;
    }

    public function isUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    public function isVisitor(): bool
    {
        return $this->role === self::ROLE_VISITEUR;
    }

    public function getAverageRating(): ?float
    {
        return $this->averageRating;
    }

    public function setAverageRating(?float $averageRating): static
    {
        $this->averageRating = $averageRating;

        return $this;
    }

    public function getAbout(): ?string
    {
        return $this->about;
    }

    public function setAbout(?string $about): static
    {
        $this->about = $about;

        return $this;
    }

    /**
     * @return Collection<int, Car>
     */
    public function getCars(): Collection
    {
        return $this->cars;
    }

    public function addCar(Car $car): static
    {
        if (!$this->cars->contains($car)) {
            $this->cars->add($car);
            $car->setOwner($this);
        }

        return $this;
    }

    public function removeCar(Car $car): static
    {
        if ($this->cars->removeElement($car)) {
            if ($car->getOwner() === $this) {
                $car->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Carpool>
     */
    public function getCarpools(): Collection
    {
        return $this->carpools;
    }

    public function addCarpool(Carpool $carpool): static
    {
        if (!$this->carpools->contains($carpool)) {
            $this->carpools->add($carpool);
            $carpool->setDriver($this);
        }

        return $this;
    }

    public function removeCarpool(Carpool $carpool): static
    {
        if ($this->carpools->removeElement($carpool)) {
            if ($carpool->getDriver() === $this) {
                $carpool->setDriver(null);
            }
        }

        return $this;
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
            $booking->setPassenger($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            if ($booking->getPassenger() === $this) {
                $booking->setPassenger(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReceivedReviews(): Collection
    {
        return $this->receivedReviews;
    }

    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setAuthor($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            if ($review->getAuthor() === $this) {
                $review->setAuthor(null);
            }
        }

        return $this;
    }

    public function addReceivedReview(Review $review): static
    {
        if (!$this->receivedReviews->contains($review)) {
            $this->receivedReviews->add($review);
            $review->setTarget($this);
        }

        return $this;
    }

    public function removeReceivedReview(Review $review): static
    {
        if ($this->receivedReviews->removeElement($review)) {
            if ($review->getTarget() === $this) {
                $review->setTarget(null);
            }
        }

        return $this;
    }
}
