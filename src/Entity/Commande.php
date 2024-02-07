<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'commandes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $utilisateur = null;

    #[ORM\OneToOne(targetEntity: Creneau::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Creneau $creneau;

    #[ORM\OneToMany(targetEntity: CommandeProduit::class, mappedBy: 'commande')]
    private Collection $commandeProduits;

    public function __construct()
    {
        $this->commandeProduits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): ?Client
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Client $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getCreneau(): ?Creneau
    {
        return $this->creneau;
    }

    public function setCreneau(?Creneau $creneau): self
    {
        $this->creneau = $creneau;
        return $this;
    }

    /**
     * @return Collection<int, CommandeProduit>
     */
    public function getCommandeProduits(): Collection
    {
        return $this->commandeProduits;
    }

    public function addCommandeProduit(CommandeProduit $commandeProduit): static
    {
        if (!$this->commandeProduits->contains($commandeProduit)) {
            $this->commandeProduits->add($commandeProduit);
            $commandeProduit->setCommande($this);
        }

        return $this;
    }

    public function removeCommandeProduit(CommandeProduit $commandeProduit): static
    {
        if ($this->commandeProduits->removeElement($commandeProduit)) {
            // set the owning side to null (unless already changed)
            if ($commandeProduit->getCommande() === $this) {
                $commandeProduit->setCommande(null);
            }
        }

        return $this;
    }
}
