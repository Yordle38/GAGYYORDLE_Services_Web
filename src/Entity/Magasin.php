<?php

namespace App\Entity;

use App\Repository\MagasinRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MagasinRepository::class)]
class Magasin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $lieu = null;

    #[ORM\Column(type: "float")]
    private ?float $latitude = null;

    #[ORM\Column(type: "float")]
    private ?float $longitude = null;

    #[ORM\OneToMany(targetEntity: Stock::class, mappedBy: 'magasin', orphanRemoval: true)]
    private Collection $stocks;

    #[ORM\OneToMany(targetEntity: Vendeur::class, mappedBy: 'Magasin')]
    private Collection $vendeurs;

    public function __construct()
    {
        $this->stocks = new ArrayCollection();
        $this->vendeurs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(string $lieu): static
    {
        $this->lieu = $lieu;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    /**
     * @return Collection<int, Stock>
     */
    public function getStocks(): Collection
    {
        return $this->stocks;
    }

    public function addStock(Stock $stock): static
    {
        if (!$this->stocks->contains($stock)) {
            $this->stocks->add($stock);
            $stock->setMagasin($this);
        }

        return $this;
    }

    public function removeStock(Stock $stock): static
    {
        if ($this->stocks->removeElement($stock)) {
            // set the owning side to null (unless already changed)
            if ($stock->getMagasin() === $this) {
                $stock->setMagasin(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Vendeur>
     */
    public function getVendeurs(): Collection
    {
        return $this->vendeurs;
    }

    public function addVendeur(Vendeur $vendeur): static
    {
        if (!$this->vendeurs->contains($vendeur)) {
            $this->vendeurs->add($vendeur);
            $vendeur->setMagasin($this);
        }

        return $this;
    }

    public function removeVendeur(Vendeur $vendeur): static
    {
        if ($this->vendeurs->removeElement($vendeur)) {
            // set the owning side to null (unless already changed)
            if ($vendeur->getMagasin() === $this) {
                $vendeur->setMagasin(null);
            }
        }

        return $this;
    }
}
