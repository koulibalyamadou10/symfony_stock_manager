<?php

namespace App\Service;

use App\Repository\ProduitRepository;
use App\Repository\UserRepository;

class StockAlertService
{
    private const SEUIL_STOCK_CRITIQUE = 5;

    public function __construct(
        private ProduitRepository $produitRepository,
        private UserRepository $userRepository,
        private EmailService $emailService
    ) {}

    /**
     * Vérifie et envoie les alertes de stock faible
     */
    public function verifierEtEnvoyerAlertes(): void
    {
        $produitsEnRupture = $this->produitRepository->findProduitsStockFaible(self::SEUIL_STOCK_CRITIQUE);
        
        if (!empty($produitsEnRupture)) {
            $this->emailService->envoyerAlerteStockFaible($produitsEnRupture);
        }
    }

    /**
     * Récupère les produits avec un stock critique
     */
    public function getProduitsStockCritique(): array
    {
        return $this->produitRepository->findProduitsStockFaible(self::SEUIL_STOCK_CRITIQUE);
    }
}