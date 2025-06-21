<?php

namespace App\Service;

use App\Entity\Produit;
use Doctrine\ORM\EntityManagerInterface;

class ProduitService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EmailService $emailService,
        private StockAlertService $stockAlertService
    ) {}

    /**
     * Effectue une vente d'un produit
     */
    public function vendre(Produit $produit, int $quantite): bool
    {
        if ($quantite <= 0 || $quantite > $produit->getQuantite()) {
            return false;
        }

        $success = $produit->vendre($quantite);
        
        if ($success) {
            $this->entityManager->flush();
            
            // Envoyer une notification de vente au propriétaire
            try {
                $this->emailService->envoyerNotificationVente($produit, $quantite, $this->getCurrentUser());
            } catch (\Exception $e) {
                // Log l'erreur mais ne pas faire échouer la vente
            }
            
            // Vérifier si des alertes de stock doivent être envoyées
            $this->stockAlertService->verifierEtEnvoyerAlertes();
        }

        return $success;
    }

    /**
     * Réactive un produit si la quantité est supérieure à 0
     */
    public function reactiverSiStock(Produit $produit): void
    {
        if ($produit->getQuantite() > 0 && !$produit->isActif()) {
            $produit->setActif(true);
            $this->entityManager->flush();
        }
    }

    /**
     * Récupère l'utilisateur actuel (à adapter selon votre contexte)
     */
    private function getCurrentUser()
    {
        // Cette méthode devrait récupérer l'utilisateur connecté
        // Pour simplifier, on retourne null ici
        return null;
    }
}