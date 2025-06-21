<?php

namespace App\Service;

use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SubscriptionService
{
    private EntityManagerInterface $entityManager;
    private SubscriptionRepository $subscriptionRepository;
    private LengoPayService $lengoPayService;
    private EmailService $emailService;
    private LoggerInterface $logger;
    private UrlGeneratorInterface $urlGenerator;
    private float $subscriptionAmount;

    public function __construct(
        EntityManagerInterface $entityManager,
        SubscriptionRepository $subscriptionRepository,
        LengoPayService $lengoPayService,
        EmailService $emailService,
        LoggerInterface $logger,
        UrlGeneratorInterface $urlGenerator,
        float $subscriptionAmount = 50000.0
    ) {
        $this->entityManager = $entityManager;
        $this->subscriptionRepository = $subscriptionRepository;
        $this->lengoPayService = $lengoPayService;
        $this->emailService = $emailService;
        $this->logger = $logger;
        $this->urlGenerator = $urlGenerator;
        $this->subscriptionAmount = $subscriptionAmount;
    }

    /**
     * Vérifie si un utilisateur a un abonnement actif
     */
    public function hasActiveSubscription(User $user): bool
    {
        $subscription = $this->subscriptionRepository->findActiveByUser($user);
        return $subscription !== null && $subscription->isActive();
    }

    /**
     * Obtient l'abonnement actuel d'un utilisateur
     */
    public function getCurrentSubscription(User $user): ?Subscription
    {
        return $this->subscriptionRepository->findActiveByUser($user);
    }

    /**
     * Crée un nouvel abonnement pour un utilisateur
     */
    public function createSubscription(User $user): Subscription
    {
        // Vérifier s'il existe déjà un abonnement
        $existingSubscription = $this->getCurrentSubscription($user);
        if ($existingSubscription) {
            return $existingSubscription;
        }

        $subscription = new Subscription();
        $subscription->setUser($user);
        $subscription->setAmount((string) $this->subscriptionAmount);

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        $this->logger->info('Nouvel abonnement créé', [
            'user_id' => $user->getId(),
            'subscription_id' => $subscription->getId()
        ]);

        return $subscription;
    }

    /**
     * Initie un paiement d'abonnement
     */
    public function initiatePayment(User $user): array
    {
        try {
            $subscription = $this->createSubscription($user);

            $returnUrl = $this->urlGenerator->generate('app_subscription_success', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $callbackUrl = $this->urlGenerator->generate('app_subscription_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);

            $paymentResult = $this->lengoPayService->createPaymentUrl(
                $this->subscriptionAmount,
                $returnUrl,
                $callbackUrl
            );

            if ($paymentResult['success']) {
                $subscription->setPaymentId($paymentResult['pay_id']);
                $this->entityManager->flush();

                $this->logger->info('Paiement initié avec succès', [
                    'user_id' => $user->getId(),
                    'subscription_id' => $subscription->getId(),
                    'pay_id' => $paymentResult['pay_id']
                ]);

                return [
                    'success' => true,
                    'payment_url' => $paymentResult['payment_url'],
                    'subscription' => $subscription
                ];
            } else {
                $this->logger->error('Échec de l\'initiation du paiement', [
                    'user_id' => $user->getId(),
                    'error' => $paymentResult['error']
                ]);

                return [
                    'success' => false,
                    'error' => $paymentResult['error']
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('Exception lors de l\'initiation du paiement', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Erreur lors de l\'initiation du paiement'
            ];
        }
    }

    /**
     * Confirme un paiement d'abonnement
     */
    public function confirmPayment(string $paymentId, array $callbackData = []): bool
    {
        try {
            $subscription = $this->subscriptionRepository->findByPaymentId($paymentId);
            
            if (!$subscription) {
                $this->logger->error('Abonnement non trouvé pour le paiement', ['payment_id' => $paymentId]);
                return false;
            }

            // Vérifier si le paiement est déjà confirmé
            if ($subscription->getStatus() === 'active') {
                return true;
            }

            $subscription->activate();
            $this->entityManager->flush();

            // Envoyer un email de confirmation
            try {
                $this->emailService->envoyerConfirmationAbonnement($subscription->getUser(), $subscription);
            } catch (\Exception $e) {
                $this->logger->warning('Erreur lors de l\'envoi de l\'email de confirmation', [
                    'error' => $e->getMessage()
                ]);
            }

            $this->logger->info('Paiement confirmé avec succès', [
                'payment_id' => $paymentId,
                'subscription_id' => $subscription->getId(),
                'user_id' => $subscription->getUser()->getId()
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la confirmation du paiement', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Désactive les abonnements expirés
     */
    public function deactivateExpiredSubscriptions(): int
    {
        $expiredSubscriptions = $this->subscriptionRepository->findExpired();
        $count = 0;

        foreach ($expiredSubscriptions as $subscription) {
            $subscription->setIsActive(false);
            $subscription->setStatus('expired');
            $count++;

            $this->logger->info('Abonnement expiré désactivé', [
                'subscription_id' => $subscription->getId(),
                'user_id' => $subscription->getUser()->getId()
            ]);
        }

        if ($count > 0) {
            $this->entityManager->flush();
        }

        return $count;
    }

    /**
     * Envoie des rappels d'expiration
     */
    public function sendExpirationReminders(): int
    {
        $expiringSoon = $this->subscriptionRepository->findExpiringSoon();
        $count = 0;

        foreach ($expiringSoon as $subscription) {
            try {
                $this->emailService->envoyerRappelExpiration($subscription->getUser(), $subscription);
                $count++;
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de l\'envoi du rappel d\'expiration', [
                    'subscription_id' => $subscription->getId(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $count;
    }

    /**
     * Obtient les statistiques des abonnements
     */
    public function getSubscriptionStats(): array
    {
        return [
            'active_subscriptions' => $this->subscriptionRepository->countActive(),
            'monthly_revenue' => $this->subscriptionRepository->getMonthlyRevenue(),
            'expiring_soon' => count($this->subscriptionRepository->findExpiringSoon()),
            'expired' => count($this->subscriptionRepository->findExpired())
        ];
    }
}