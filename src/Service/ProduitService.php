<?php

namespace App\Service;

use App\Entity\Produit;
use Doctrine\ORM\EntityManagerInterface;

class ProduitService
{
    public function __construct(
        private EntityManagerInterface $entityManager
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
}