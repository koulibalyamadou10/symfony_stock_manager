<?php

namespace App\Entity;

use App\Repository\SubscriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
class Subscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'subscription', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column]
    private ?bool $isActive = false;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = '50000.00';

    #[ORM\Column(length: 3)]
    private ?string $currency = 'GNF';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $paymentId = null;

    #[ORM\Column(length: 50)]
    private ?string $status = 'pending';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $paidAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = 'pending';
        $this->amount = '50000.00';
        $this->currency = 'GNF';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive && $this->endDate > new \DateTime();
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function getPaymentId(): ?string
    {
        return $this->paymentId;
    }

    public function setPaymentId(?string $paymentId): static
    {
        $this->paymentId = $paymentId;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getPaidAt(): ?\DateTimeInterface
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeInterface $paidAt): static
    {
        $this->paidAt = $paidAt;
        return $this;
    }

    /**
     * Vérifie si l'abonnement est expiré
     */
    public function isExpired(): bool
    {
        return $this->endDate < new \DateTime();
    }

    /**
     * Vérifie si l'abonnement expire bientôt (dans les 7 jours)
     */
    public function isExpiringSoon(): bool
    {
        $sevenDaysFromNow = new \DateTime('+7 days');
        return $this->endDate <= $sevenDaysFromNow;
    }

    /**
     * Active l'abonnement pour un mois
     */
    public function activate(): void
    {
        $this->isActive = true;
        $this->status = 'active';
        $this->startDate = new \DateTime();
        $this->endDate = new \DateTime('+1 month');
        $this->paidAt = new \DateTime();
    }

    /**
     * Renouvelle l'abonnement pour un mois supplémentaire
     */
    public function renew(): void
    {
        if ($this->isExpired()) {
            $this->startDate = new \DateTime();
            $this->endDate = new \DateTime('+1 month');
        } else {
            $this->endDate = $this->endDate->modify('+1 month');
        }
        $this->isActive = true;
        $this->status = 'active';
        $this->paidAt = new \DateTime();
    }

    public function __toString(): string
    {
        return sprintf('Abonnement %s - %s', $this->user?->getNom(), $this->status);
    }
}