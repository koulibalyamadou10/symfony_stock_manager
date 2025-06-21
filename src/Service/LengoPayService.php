<?php

namespace App\Service;

use App\Entity\Abonnement;
use App\Entity\User;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class LengoPayService
{
    private string $apiKey;
    private string $merchantId;
    private const API_BASE_URL = 'https://api.lengopay.com/v1';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private ParameterBagInterface $params
    ) {
        $this->apiKey = $this->params->get('lengo_pay_api_key');
        $this->merchantId = $this->params->get('lengo_pay_merchant_id');
    }

    /**
     * Crée une transaction pour un nouvel abonnement
     */
    public function creerTransaction(User $user, float $montant): array
    {
        try {
            $response = $this->httpClient->request('POST', self::API_BASE_URL . '/transactions', [
                'headers' => $this->getHeaders(),
                'json' => [
                    'amount' => $montant,
                    'currency' => 'GNF',
                    'merchant_id' => $this->merchantId,
                    'description' => 'Abonnement mensuel - Gestion de Stock',
                    'callback_url' => $this->params->get('app.url') . '/abonnement/confirmation',
                    'customer' => [
                        'name' => $user->getNom(),
                        'email' => $user->getEmail()
                    ],
                    'metadata' => [
                        'user_id' => $user->getId(),
                        'type' => 'abonnement_mensuel'
                    ]
                ]
            ]);

            $data = $response->toArray();
            
            $this->logger->info('Transaction Lengo Pay créée', [
                'transaction_id' => $data['id'],
                'user_id' => $user->getId(),
                'montant' => $montant
            ]);

            return $data;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création de la transaction Lengo Pay', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);
            throw $e;
        }
    }

    /**
     * Vérifie le statut d'une transaction
     */
    public function verifierTransaction(string $transactionId): array
    {
        try {
            $response = $this->httpClient->request('GET', self::API_BASE_URL . '/transactions/' . $transactionId, [
                'headers' => $this->getHeaders()
            ]);

            return $response->toArray();

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la vérification de la transaction', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId
            ]);
            throw $e;
        }
    }

    /**
     * Vérifie si une transaction est valide pour un abonnement
     */
    public function validerTransactionAbonnement(string $transactionId, Abonnement $abonnement): bool
    {
        try {
            $transaction = $this->verifierTransaction($transactionId);

            // Vérifier que la transaction est réussie et correspond au bon montant
            if ($transaction['status'] !== 'completed') {
                $this->logger->warning('Transaction non complétée', [
                    'transaction_id' => $transactionId,
                    'status' => $transaction['status']
                ]);
                return false;
            }

            if ($transaction['amount'] != $abonnement->getMontant()) {
                $this->logger->warning('Montant de la transaction incorrect', [
                    'transaction_id' => $transactionId,
                    'montant_attendu' => $abonnement->getMontant(),
                    'montant_recu' => $transaction['amount']
                ]);
                return false;
            }

            // Vérifier que la transaction correspond au bon utilisateur
            if ($transaction['metadata']['user_id'] != $abonnement->getUtilisateur()->getId()) {
                $this->logger->warning('Utilisateur de la transaction incorrect', [
                    'transaction_id' => $transactionId,
                    'user_id_attendu' => $abonnement->getUtilisateur()->getId(),
                    'user_id_recu' => $transaction['metadata']['user_id']
                ]);
                return false;
            }

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la validation de la transaction', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
                'abonnement_id' => $abonnement->getId()
            ]);
            return false;
        }
    }

    /**
     * Génère les en-têtes pour les requêtes API
     */
    private function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
    }

    /**
     * Génère une référence unique pour une transaction
     */
    public function genererReference(): string
    {
        return uniqid('ABO-', true);
    }

    /**
     * Récupère l'URL de paiement pour une transaction
     */
    public function getUrlPaiement(string $transactionId): string
    {
        return sprintf(
            'https://checkout.lengopay.com/pay/%s?merchant_id=%s',
            $transactionId,
            $this->merchantId
        );
    }
}
