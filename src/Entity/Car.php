<?php

namespace App\Entity;

use App\Repository\CarRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Carpool;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CarRepository::class)]
#[ORM\Table(name: 'voiture')] 
class Car
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'marque', length: 50)] 
    private ?string $brand = null; 

    #[ORM\Column(name: 'modele', length: 50)] 
    private ?string $model = null; 

    #[ORM\Column(name: 'couleur', length: 30)] 
    private ?string $color = null; 

    #[ORM\Column(name: 'type_energie', length: 30)] 
    private ?string $fuelType = null; 

    #[ORM\Column(name: 'immatriculation', length: 20)] 
    private ?string $registration = null; 

    #[ORM\Column(name: 'nb_places')] 
    private ?int $seats = null; 

    #[ORM\Column(name: 'preferences_chauffeur', nullable: true)] 
    private ?array $driverPreferences = null; 

    #[ORM\Column(name: 'autres_preferences', length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le texte ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $otherPreferences = null;

    #[ORM\ManyToOne(inversedBy: 'cars')]
    #[ORM\JoinColumn(name: "proprietaire_id", nullable: true)] // TODO: rendre NOT NULL après API 
    private ?User $owner = null; 

    #[ORM\OneToMany(mappedBy: 'car', targetEntity: Carpool::class, orphanRemoval: true)]
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

    public function setFuelType(string $fuelType): static
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
            // set the owning side to null (unless already changed)
            if ($carpool->getCar() === $this) {
                $carpool->setCar(null);
            }
        }

        return $this;
    }

}
