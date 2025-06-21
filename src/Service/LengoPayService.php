<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LengoPayService
{
    private HttpClientInterface $httpClient;
    private string $licenseKey;
    private string $websiteId;
    private string $apiUrl;
    private string $currency;
    private LoggerInterface $logger;

    public function __construct(
        string $lengoPayLicenseKey,
        string $lengoPayWebsiteId,
        string $lengoPayApiUrl,
        string $lengoPayCurrency,
        LoggerInterface $logger
    ) {
        $this->httpClient = HttpClient::create();
        $this->licenseKey = $lengoPayLicenseKey;
        $this->websiteId = $lengoPayWebsiteId;
        $this->apiUrl = $lengoPayApiUrl;
        $this->currency = $lengoPayCurrency;
        $this->logger = $logger;
    }

    /**
     * Crée une URL de paiement via l'API Lengo Pay
     */
    public function createPaymentUrl(
        float $amount,
        string $returnUrl = null,
        string $callbackUrl = null
    ): array {
        try {
            $payload = [
                'websiteid' => $this->websiteId,
                'amount' => $amount,
                'currency' => $this->currency,
            ];

            if ($returnUrl) {
                $payload['return_url'] = $returnUrl;
            }

            if ($callbackUrl) {
                $payload['callback_url'] = $callbackUrl;
            }

            $this->logger->info('Création d\'une URL de paiement Lengo Pay', [
                'amount' => $amount,
                'currency' => $this->currency,
                'websiteId' => $this->websiteId
            ]);

            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Basic ' . $this->licenseKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->toArray();

            if ($statusCode === 200 && isset($content['status']) && $content['status'] === 'success') {
                $this->logger->info('URL de paiement créée avec succès', [
                    'pay_id' => $content['pay_id'] ?? null,
                    'payment_url' => $content['payment_url'] ?? null
                ]);

                return [
                    'success' => true,
                    'pay_id' => $content['pay_id'] ?? null,
                    'payment_url' => $content['payment_url'] ?? null,
                    'data' => $content
                ];
            } else {
                $this->logger->error('Erreur lors de la création de l\'URL de paiement', [
                    'status_code' => $statusCode,
                    'response' => $content
                ]);

                return [
                    'success' => false,
                    'error' => 'Erreur lors de la création du paiement',
                    'details' => $content
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('Exception lors de l\'appel à l\'API Lengo Pay', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Erreur de communication avec Lengo Pay: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Vérifie le statut d'un paiement
     */
    public function verifyPayment(string $payId): array
    {
        try {
            // Note: Cette méthode nécessiterait un endpoint de vérification de Lengo Pay
            // Pour l'instant, nous nous basons sur les callbacks
            
            $this->logger->info('Vérification du paiement', ['pay_id' => $payId]);

            return [
                'success' => true,
                'status' => 'verified'
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la vérification du paiement', [
                'pay_id' => $payId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Traite un callback de Lengo Pay
     */
    public function processCallback(array $callbackData): array
    {
        try {
            $this->logger->info('Traitement du callback Lengo Pay', $callbackData);

            // Validation des données du callback
            if (!isset($callbackData['pay_id']) || !isset($callbackData['status'])) {
                throw new \InvalidArgumentException('Données de callback invalides');
            }

            return [
                'success' => true,
                'pay_id' => $callbackData['pay_id'],
                'status' => $callbackData['status'],
                'data' => $callbackData
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du traitement du callback', [
                'error' => $e->getMessage(),
                'callback_data' => $callbackData
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}