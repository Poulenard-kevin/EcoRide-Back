<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(name="utilisateur")
 * @ApiResource(
 *   normalizationContext={"groups"={"user:read"}},
 *   denormalizationContext={"groups"={"user:write"}},
 *   collectionOperations={
 *     "get"={
 *       "path"="/users",
 *       "security"="is_granted('ROLE_ADMIN')",
 *       "openapi_context"={
 *         "summary"="Récupère la liste des utilisateurs",
 *         "description"="Retourne la liste paginée des utilisateurs. Accessible uniquement aux administrateurs.",
 *         "responses"={
 *           "200"={"description"="Liste des utilisateurs (paginated)"}
 *         }
 *       }
 *     },
 *     "post"={
 *       "path"="/users",
 *       "security"="is_granted('IS_AUTHENTICATED_ANONYMOUSLY')",
 *       "validation_groups"={"Default","Registration"},
 *       "openapi_context"={
 *         "summary"="Crée un nouvel utilisateur",
 *         "description"="Inscription d'un nouvel utilisateur. Le mot de passe en clair (plainPassword) sera traité et haché côté serveur.",
 *         "responses"={
 *           "201"={"description"="Utilisateur créé"},
 *           "400"={"description"="Erreurs de validation (ex: email déjà utilisé)"}
 *         }
 *       }
 *     }
 *   },
 *   itemOperations={
 *     "get"={
 *       "path"="/users/{id}",
 *       "security"="is_granted('ROLE_USER') and (object == user or is_granted('ROLE_ADMIN'))",
 *       "openapi_context"={
 *         "summary"="Récupère un utilisateur",
 *         "description"="Retourne les informations publiques d'un utilisateur. Les champs sensibles ne sont pas exposés ici.",
 *         "responses"={
 *           "200"={"description"="Détails d'un utilisateur"},
 *           "403"={"description"="Accès refusé si non autorisé"}
 *         }
 *       }
 *     },
 *     "put"={
 *       "path"="/users/{id}",
 *       "security"="object == user or is_granted('ROLE_ADMIN')",
 *       "openapi_context"={
 *         "summary"="Met à jour un utilisateur",
 *         "description"="Permet au propriétaire ou à un admin de modifier le profil. Ne permet pas de modifier les rôles côté client.",
 *         "responses"={
 *           "200"={"description"="Utilisateur mis à jour"},
 *           "403"={"description"="Accès refusé"}
 *         }
 *       }
 *     },
 *     "delete"={
 *       "path"="/users/{id}",
 *       "security"="is_granted('ROLE_ADMIN') or (user and object.getId() == user.getId())",
 *       "openapi_context"={
 *         "summary"="Supprime un utilisateur",
 *         "description"="Permet à l'utilisateur de supprimer son propre compte ou à un administrateur de supprimer n'importe quel compte.",
 *         "responses"={
 *           "204"={"description"="Utilisateur supprimé"},
 *           "403"={"description"="Accès refusé"}
 *         }
 *       }
 *     }
 *   }
 * )
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $apiToken = null;

    /**
     * @Groups({"user:read"})
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @Assert\NotBlank(message="Le nom est obligatoire.")
     * @Assert\Regex(
     *     pattern="/^[A-Za-zÀ-ÖØ-öø-ÿ\s'-]{2,}$/u",
     *     message="Le nom doit contenir au moins 2 lettres et ne peut contenir que des lettres, espaces, apostrophes ou tirets."
     * )
     * @Groups({"user:read", "user:write", "review:read"})
     * @ORM\Column(name="nom", type="string", length=100)
     */
    private ?string $lastName = null;

    /**
     * @Assert\NotBlank(message="Le prénom est obligatoire.")
     * @Assert\Regex(
     *     pattern="/^[A-Za-zÀ-ÖØ-öø-ÿ\s'-]{2,}$/u",
     *     message="Le prénom doit contenir au moins 2 lettres et ne peut contenir que des lettres, espaces, apostrophes ou tirets."
     * )
     * @Groups({"user:read", "user:write", "review:read"})
     * @ORM\Column(name="prenom", type="string", length=100)
     */
    private ?string $firstName = null;

    /**
     * @Assert\NotBlank(message="L'email est obligatoire.")
     * @Assert\Email(message="L'email n'est pas valide.")
     * @Groups({"user:admin_read", "user:write"})
     * @ORM\Column(name="email", type="string", length=180)
     */
    private ?string $email = null;

    /**
     * // pas de groupe user:write ici : le hash ne doit pas être envoyé par le client
     * @ORM\Column(type="string", length=255)
     */
    private ?string $password = null;

    /**
     * @Assert\NotBlank(message="Le mot de passe est obligatoire.", groups={"Registration"})
     * @Assert\Regex(
     *     pattern="/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]{8,}$/",
     *     message="Le mot de passe doit contenir au moins 8 caractères, une majuscule, un chiffre et un symbole.",
     *     groups={"Registration"}
     * )
     * @Groups({"user:write"})
     */
    private ?string $plainPassword = null;

    public const ROLE_VISITEUR = 'ROLE_VISITEUR';
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_EMPLOYE = 'ROLE_EMPLOYE';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    /**
     * @Groups({"user:read"})
     * @ORM\Column(name="note_moyenne", type="float", nullable=true)
     */
    private ?float $averageRating = null;

    /**
     * @Groups({"user:read", "user:write"})
     * @ORM\Column(name="a_propos", type="text", nullable=true)
     * @Assert\Length(
     *     max=500,
     *     maxMessage="Le texte ne peut pas dépasser 500 caractères."
     * )
     */
    private ?string $about = null;

    /**
     * @Groups({"user:admin_read", "user:read"})
     * @ORM\Column(type="json")
     */
    private array $roles = [];

    /**
     * @Groups({"user:admin_read"})
     * @ORM\Column(type="boolean")
     */
    private bool $isActive = true;

    /**
     * @ORM\OneToMany(targetEntity=Car::class, mappedBy="owner", orphanRemoval=true)
     */
    private Collection $cars;

    /**
     * @Groups({"user:read"})
     * @ORM\OneToMany(targetEntity=Carpool::class, mappedBy="driver")
     */
    private Collection $carpools;

    /**
     * @ORM\OneToMany(targetEntity=Booking::class, mappedBy="passenger")
     */
    private Collection $bookings;

    /**
     * @Groups({"user:read"})
     * @ORM\OneToMany(targetEntity=Review::class, mappedBy="author", orphanRemoval=true)
     */
    private Collection $reviews;

    /**
     * @Groups({"user:read"})
     * @ORM\OneToMany(targetEntity=Review::class, mappedBy="target", orphanRemoval=true)
     */
    private Collection $receivedReviews;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $lastPasswordResetRequestAt = null;

    public function __construct()
    {
        $this->roles = [self::ROLE_USER];
        $this->cars = new ArrayCollection();
        $this->carpools = new ArrayCollection();
        $this->bookings = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->receivedReviews = new ArrayCollection();
    }

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setApiToken(?string $apiToken): self
    {
        $this->apiToken = $apiToken;
        return $this;
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

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        if (empty($roles)) {
            $roles[] = self::ROLE_USER;
        }
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = array_values($roles);
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

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    // Méthodes utilitaires basées sur les rôles
    public function isAdmin(): bool
    {
        return in_array(self::ROLE_ADMIN, $this->getRoles(), true);
    }

    public function isEmployee(): bool
    {
        return in_array(self::ROLE_EMPLOYE, $this->getRoles(), true);
    }

    public function isUser(): bool
    {
        return in_array(self::ROLE_USER, $this->getRoles(), true);
    }

    public function isVisitor(): bool
    {
        return in_array(self::ROLE_VISITEUR, $this->getRoles(), true);
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

    // Relations
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
        if ($this->cars->removeElement($car) && $car->getOwner() === $this) {
            $car->setOwner(null);
        }
        return $this;
    }

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
        if ($this->carpools->removeElement($carpool) && $carpool->getDriver() === $this) {
            $carpool->setDriver(null);
        }
        return $this;
    }

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
        if ($this->bookings->removeElement($booking) && $booking->getPassenger() === $this) {
            $booking->setPassenger(null);
        }
        return $this;
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
        if ($this->reviews->removeElement($review) && $review->getAuthor() === $this) {
            $review->setAuthor(null);
        }
        return $this;
    }

    public function getReceivedReviews(): Collection
    {
        return $this->receivedReviews;
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
        if ($this->receivedReviews->removeElement($review) && $review->getTarget() === $this) {
            $review->setTarget(null);
        }
        return $this;
    }

    public function getLastPasswordResetRequestAt(): ?\DateTimeInterface
    {
        return $this->lastPasswordResetRequestAt;
    }

    public function setLastPasswordResetRequestAt(?\DateTimeInterface $date): self
    {
        $this->lastPasswordResetRequestAt = $date;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }
}