<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Il existe déjà un compte avec cet email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'L\'email ne peut pas être vide')]
    #[Assert\Email(message: 'Veuillez entrer un email valide')]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom ne peut pas être vide')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Le nom doit contenir au moins {{ limit }} caractères')]
    private ?string $nom = null;

    /**
     * @var Collection<int, Produit>
     */
    #[ORM\OneToMany(targetEntity: Produit::class, mappedBy: 'utilisateur')]
    private Collection $produits;

    /**
     * @var Collection<int, Abonnement>
     */
    #[ORM\OneToMany(targetEntity: Abonnement::class, mappedBy: 'utilisateur')]
    private Collection $abonnements;

    public function __construct()
    {
        $this->produits = new ArrayCollection();
        $this->abonnements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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

    /**
     * @return Collection<int, Produit>
     */
    public function getProduits(): Collection
    {
        return $this->produits;
    }

    public function addProduit(Produit $produit): static
    {
        if (!$this->produits->contains($produit)) {
            $this->produits->add($produit);
            $produit->setUtilisateur($this);
        }

        return $this;
    }

    public function removeProduit(Produit $produit): static
    {
        if ($this->produits->removeElement($produit)) {
            // set the owning side to null (unless already changed)
            if ($produit->getUtilisateur() === $this) {
                $produit->setUtilisateur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Abonnement>
     */
    public function getAbonnements(): Collection
    {
        return $this->abonnements;
    }

    public function addAbonnement(Abonnement $abonnement): static
    {
        if (!$this->abonnements->contains($abonnement)) {
            $this->abonnements->add($abonnement);
            $abonnement->setUtilisateur($this);
        }

        return $this;
    }

    public function removeAbonnement(Abonnement $abonnement): static
    {
        if ($this->abonnements->removeElement($abonnement)) {
            // set the owning side to null (unless already changed)
            if ($abonnement->getUtilisateur() === $this) {
                $abonnement->setUtilisateur(null);
            }
        }

        return $this;
    }

    /**
     * Récupère l'abonnement actif de l'utilisateur
     */
    public function getAbonnementActif(): ?Abonnement
    {
        foreach ($this->abonnements as $abonnement) {
            if ($abonnement->isEstActif() && $abonnement->getDateFin() > new \DateTime()) {
                return $abonnement;
            }
        }
        return null;
    }

    /**
     * Vérifie si l'utilisateur a un abonnement actif
     */
    public function hasAbonnementActif(): bool
    {
        return $this->getAbonnementActif() !== null;
    }

    /**
     * Vérifie si l'abonnement expire bientôt (dans les 3 jours)
     */
    public function hasAbonnementExpirantBientot(): bool
    {
        $abonnement = $this->getAbonnementActif();
        if (!$abonnement) {
            return false;
        }

        $dateLimite = (new \DateTime())->modify('+3 days');
        return $abonnement->getDateFin() <= $dateLimite;
    }

    /**
     * Récupère la date d'expiration de l'abonnement actif
     */
    public function getDateExpirationAbonnement(): ?\DateTimeInterface
    {
        $abonnement = $this->getAbonnementActif();
        return $abonnement ? $abonnement->getDateFin() : null;
    }

    /**
     * Vérifie si l'utilisateur peut accéder aux fonctionnalités premium
     */
    public function canAccessPremiumFeatures(): bool
    {
        // Les admins ont toujours accès
        if (in_array('ROLE_ADMIN', $this->getRoles())) {
            return true;
        }

        // Les autres utilisateurs doivent avoir un abonnement actif
        return $this->hasAbonnementActif();
    }

    public function __toString(): string
    {
        return $this->nom ?? $this->email ?? '';
    }
}
