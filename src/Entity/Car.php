<?php

namespace App\Entity;

use App\Repository\CarRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Core\Annotation\ApiProperty;
use DateTimeInterface;

/**
 * @ORM\Entity(repositoryClass=CarRepository::class)
 * @ORM\Table(name="voiture")
 * @ApiResource(
 *     normalizationContext={"groups"={"car:read"}},
 *     denormalizationContext={"groups"={"car:write"}},
 *     collectionOperations={
 *         "get"={
 *           "path"="/cars",
 *           "security"="is_granted('ROLE_USER')",
 *           "openapi_context"={
 *             "summary"="Récupère la liste des voitures (uniquement celles de l'utilisateur courant sauf admin)",
 *             "description"="Retourne la liste des véhicules appartenant à l'utilisateur connecté. Les administrateurs reçoivent toutes les voitures.",
 *             "responses"={
 *               "200"={"description"="Liste des voitures (paginated)"}
 *             }
 *           }
 *         },
 *         "post"={
 *           "path"="/cars",
 *           "security"="is_granted('ROLE_USER') or is_granted('ROLE_ADMIN')",
 *           "openapi_context"={
 *             "summary"="Crée une nouvelle voiture",
 *             "description"="Enregistre un véhicule. Le champ `owner` est défini automatiquement côté serveur (utilisateur courant). Ne pas envoyer `owner` dans le payload.",
 *             "responses"={
 *               "201"={"description"="Voiture créée"},
 *               "400"={"description"="Erreurs de validation"}
 *             }
 *           }
 *         }
 *     },
 *     itemOperations={
 *         "get"={
 *           "path"="/cars/{id}",
 *           "security"="object.getOwner() == user or is_granted('ROLE_ADMIN')",
 *           "openapi_context"={
 *             "summary"="Récupère une voiture",
 *             "description"="Détails d'un véhicule. Les informations sensibles (si tu en as) ne sont pas exposées.",
 *             "responses"={
 *               "200"={"description"="Détails de la voiture"},
 *               "404"={"description"="Voiture introuvable"}
 *             }
 *           }
 *         },
 *         "put"={
 *           "path"="/cars/{id}",
 *           "security"="object.getOwner() == user or is_granted('ROLE_ADMIN')",
 *           "openapi_context"={
 *             "summary"="Met à jour une voiture",
 *             "description"="Permet au propriétaire du véhicule ou à un administrateur de modifier ses informations. Le champ `owner` ne peut pas être modifié via cette opération.",
 *             "responses"={
 *               "200"={"description"="Voiture mise à jour"},
 *               "403"={"description"="Accès refusé"}
 *             }
 *           }
 *         },
 *         "delete"={
 *           "path"="/cars/{id}",
 *           "security"="object.getOwner() == user or is_granted('ROLE_ADMIN')",
 *           "openapi_context"={
 *             "summary"="Supprime une voiture",
 *             "description"="Permet au propriétaire du véhicule ou à un administrateur de supprimer le véhicule.",
 *             "responses"={
 *               "204"={"description"="Voiture supprimée"}
 *             }
 *           }
 *         }
 *     }
 * )
 */
class Car
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"car:read"})
     */
    private ?int $id = null;

    /**
     * @ORM\Column(name="marque", type="string", length=50)
     * @Assert\NotBlank(message="La marque est obligatoire.")
     * @Groups({"car:read", "car:write"})
     */
    private ?string $brand = null;

    /**
     * @ORM\Column(name="modele", type="string", length=50)
     * @Assert\NotBlank(message="Le modèle est obligatoire.")
     * @Groups({"car:read", "car:write"})
     */
    private ?string $model = null;

    /**
     * @ORM\Column(name="couleur", type="string", length=30)
     * @Assert\NotBlank(message="La couleur est obligatoire.")
     * @Groups({"car:read", "car:write"})
     */
    private ?string $color = null;

    public const FUEL_ELECTRIC = 'Électrique';
    public const FUEL_THERMIC  = 'Thermique';
    public const FUEL_HYBRID   = 'Hybride';

    public const FUEL_CHOICES = [
        self::FUEL_ELECTRIC,
        self::FUEL_THERMIC,
        self::FUEL_HYBRID,
    ];

    /**
     * @ORM\Column(name="type_energie", type="string", length=30)
     * @Assert\NotBlank(message="Le type d'énergie est obligatoire.")
     * @Assert\Choice(choices=Car::FUEL_CHOICES, message="Type d'énergie invalide.")
     * @Groups({"car:read", "car:write"})
     * @ApiProperty(attributes={
     *     "openapi_context"={
     *         "type"="string",
     *         "enum"={
     *             "Électrique","Thermique","Hybride"
     *         }
     *     }
     * })
     */
    private ?string $fuelType = null;


    /**
     * @ORM\Column(name="immatriculation", type="string", length=20)
     * @Assert\NotBlank(message="L'immatriculation est obligatoire.")
     * @Groups({"car:read", "car:write"})
     */
    private ?string $registration = null;

    /**
     * @ORM\Column(name="nb_places", type="integer")
     * @Assert\NotBlank(message="Le nombre de places est obligatoire.")
     * @Assert\Positive(message="Le nombre de places doit être positif.")
     * @Groups({"car:read", "car:write"})
     */
    private ?int $seats = null;

    /**
     * @ORM\Column(name="preferences_chauffeur", type="json", nullable=true)
     * @Groups({"car:read", "car:write"})
     */
    private ?array $driverPreferences = null;

    /**
     * @ORM\Column(name="autres_preferences", type="string", length=255, nullable=true)
     * @Assert\Length(
     *     max=255,
     *     maxMessage="Le texte ne peut pas dépasser {{ limit }} caractères."
     * )
     * @Groups({"car:read", "car:write"})
     */
    private ?string $otherPreferences = null;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @Groups({"car:read","car:write"})
     */
    private ?DateTimeInterface $firstRegistration = null;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="cars")
     * @ORM\JoinColumn(name="proprietaire_id", referencedColumnName="id", nullable=true)
     * @Groups({"car:read"})
     */
    private ?User $owner = null;

    /**
     * @ORM\OneToMany(targetEntity=Carpool::class, mappedBy="car", orphanRemoval=true)
     */
    private Collection $carpools;

    public function __construct()
    {
        $this->carpools = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): static
    {
        $this->brand = $brand;
        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): static
    {
        $this->model = $model;
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;
        return $this;
    }

    public function getFuelType(): ?string
    {
        return $this->fuelType;
    }

    public function setFuelType(string $fuelType): self
    {
        $this->fuelType = $fuelType;
        return $this;
    }

    public function getRegistration(): ?string
    {
        return $this->registration;
    }

    public function setRegistration(string $registration): static
    {
        $this->registration = $registration;
        return $this;
    }

    public function getSeats(): ?int
    {
        return $this->seats;
    }

    public function setSeats(int $seats): static
    {
        $this->seats = $seats;
        return $this;
    }

    public function getDriverPreferences(): ?array
    {
        return $this->driverPreferences;
    }

    public function setDriverPreferences(?array $driverPreferences): static
    {
        $this->driverPreferences = $driverPreferences;
        return $this;
    }

    public function getOtherPreferences(): ?string
    {
        return $this->otherPreferences;
    }

    public function setOtherPreferences(?string $otherPreferences): static
    {
        $this->otherPreferences = $otherPreferences;
        return $this;
    }

    public function getFirstRegistration(): ?\DateTimeInterface
    {
        return $this->firstRegistration;
    }

    public function setFirstRegistration(?\DateTimeInterface $firstRegistration): self
    {
        $this->firstRegistration = $firstRegistration;
        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;
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
            $carpool->setCar($this);
        }
        return $this;
    }

    public function removeCarpool(Carpool $carpool): static
    {
        if ($this->carpools->removeElement($carpool)) {
            if ($carpool->getCar() === $this) {
                $carpool->setCar(null);
            }
        }
        return $this;
    }
}