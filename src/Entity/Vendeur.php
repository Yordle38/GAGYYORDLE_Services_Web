<?php

namespace App\Entity;

use App\Repository\VendeurRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VendeurRepository::class)]
class Vendeur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\ManyToOne(inversedBy: 'vendeurs')]
    private ?Magasin $Magasin = null;

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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getMagasin(): ?Magasin
    {
        return $this->Magasin;
    }

    public function setMagasin(?Magasin $Magasin): static
    {
        $this->Magasin = $Magasin;

        return $this;
    }
}
