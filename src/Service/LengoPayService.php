<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LengoPayService
{
    private string $licenseKey;
    private string $websiteId;
    private string $apiUrl;
    private string $currency;
    private string $appUrl;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        string $licenseKey,
        string $websiteId,
        string $apiUrl,
        string $currency,
        string $appUrl
    ) {
        $this->licenseKey = $licenseKey;
        $this->websiteId = $websiteId;
        $this->apiUrl = $apiUrl;
        $this->currency = $currency;
        $this->appUrl = $appUrl;
    }

    /**
     * Crée une URL de paiement via l'API Lengo Pay
     */
    public function createPaymentUrl(float $amount, string $returnUrl = null, string $callbackUrl = null): array
    {
        try {
            // URLs par défaut si non fournies
            $returnUrl = $returnUrl ?: $this->appUrl . '/subscription/success';
            $callbackUrl = $callbackUrl ?: $this->appUrl . '/subscription/callback';

            $this->logger->info('Création d\'une URL de paiement Lengo Pay', [
                'amount' => $amount,
                'currency' => $this->currency,
                'return_url' => $returnUrl,
                'callback_url' => $callbackUrl
            ]);

            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->licenseKey),
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'websiteid' => $this->websiteId,
                    'amount' => $amount,
                    'currency' => $this->currency,
                    'return_url' => $returnUrl,
                    'callback_url' => $callbackUrl,
                ]
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray();

            $this->logger->info('Réponse de l\'API Lengo Pay', [
                'status_code' => $statusCode,
                'response' => $data
            ]);

            if ($statusCode === 200 && isset($data['status']) && $data['status'] === 'success') {
                return [
                    'success' => true,
                    'pay_id' => $data['pay_id'],
                    'payment_url' => $data['payment_url']
                ];
            } else {
                $this->logger->error('Erreur dans la réponse Lengo Pay', [
                    'status_code' => $statusCode,
                    'response' => $data
                ]);

                return [
                    'success' => false,
                    'error' => $data['message'] ?? 'Erreur inconnue lors de la création du paiement'
                ];
            }

        } catch (\Exception $e) {
            $this->logger->error('Exception lors de la création du paiement Lengo Pay', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Erreur de communication avec le service de paiement'
            ];
        }
    }

    /**
     * Vérifie le statut d'un paiement
     */
    public function verifyPayment(string $payId): array
    {
        try {
            $this->logger->info('Vérification du paiement', ['pay_id' => $payId]);

            $response = $this->httpClient->request('GET', $this->apiUrl . '/' . $payId, [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->licenseKey),
                    'Accept' => 'application/json',
                ]
            ]);

            $data = $response->toArray();

            $this->logger->info('Statut du paiement vérifié', [
                'pay_id' => $payId,
                'status' => $data['status'] ?? 'unknown'
            ]);

            return $data;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la vérification du paiement', [
                'pay_id' => $payId,
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'error',
                'message' => 'Erreur lors de la vérification du paiement'
            ];
        }
    }

    /**
     * Valide les données du callback
     */
    public function validateCallback(array $callbackData): bool
    {
        // Validation basique des données du callback
        if (!isset($callbackData['pay_id']) || !isset($callbackData['status'])) {
            $this->logger->warning('Données de callback invalides', $callbackData);
            return false;
        }

        // Ici vous pourriez ajouter une validation de signature si Lengo Pay la fournit
        // Pour l'instant, on fait une validation basique

        return true;
    }
}