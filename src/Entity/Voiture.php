<?php

namespace App\Entity;

use App\Repository\VoitureRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VoitureRepository::class)]
class Voiture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $marque = null;

    #[ORM\Column(length: 50)]
    private ?string $modele = null;

    #[ORM\Column(length: 30)]
    private ?string $couleur = null;

    #[ORM\Column(length: 30)]
    private ?string $energie = null;

    #[ORM\Column(length: 20)]
    private ?string $immatriculation = null;

    #[ORM\Column]
    private ?int $nbPlaces = null;

    #[ORM\Column(nullable: true)]
    private ?array $preferencesChauffeur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $autresPreferences = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMarque(): ?string
    {
        return $this->marque;
    }

    public function setMarque(string $marque): static
    {
        $this->marque = $marque;

        return $this;
    }

    public function getModele(): ?string
    {
        return $this->modele;
    }

    public function setModele(string $modele): static
    {
        $this->modele = $modele;

        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(string $couleur): static
    {
        $this->couleur = $couleur;

        return $this;
    }

    public function getEnergie(): ?string
    {
        return $this->energie;
    }

    public function setEnergie(string $energie): static
    {
        $this->energie = $energie;

        return $this;
    }

    public function getImmatriculation(): ?string
    {
        return $this->immatriculation;
    }

    public function setImmatriculation(string $immatriculation): static
    {
        $this->immatriculation = $immatriculation;

        return $this;
    }

    public function getNbPlaces(): ?int
    {
        return $this->nbPlaces;
    }

    public function setNbPlaces(int $nbPlaces): static
    {
        $this->nbPlaces = $nbPlaces;

        return $this;
    }

    public function getPreferencesChauffeur(): ?array
    {
        return $this->preferencesChauffeur;
    }

    public function setPreferencesChauffeur(?array $preferencesChauffeur): static
    {
        $this->preferencesChauffeur = $preferencesChauffeur;

        return $this;
    }

    public function getAutresPreferences(): ?string
    {
        return $this->autresPreferences;
    }

    public function setAutresPreferences(?string $autresPreferences): static
    {
        $this->autresPreferences = $autresPreferences;

        return $this;
    }
}
