<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use App\Repository\CategorieRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        ProduitRepository $produitRepository,
        CategorieRepository $categorieRepository,
        UserRepository $userRepository
    ): Response {
        $user = $this->getUser();
        
        if ($this->isGranted('ROLE_ADMIN')) {
            // Statistiques pour l'admin
            $totalProduits = count($produitRepository->findAll());
            $produitsActifs = count($produitRepository->findActifs());
            $totalCategories = count($categorieRepository->findAll());
            $totalUtilisateurs = count($userRepository->findAll());
            $ruptureStock = $produitRepository->findRuptureStock();
            
            return $this->render('dashboard/admin.html.twig', [
                'totalProduits' => $totalProduits,
                'produitsActifs' => $produitsActifs,
                'totalCategories' => $totalCategories,
                'totalUtilisateurs' => $totalUtilisateurs,
                'ruptureStock' => $ruptureStock,
            ]);
        } else {
            // Tableau de bord pour utilisateur simple
            $mesProduits = $produitRepository->findByUser($user);
            $mesProduitsActifs = $produitRepository->findActifsByUser($user);
            
            return $this->render('dashboard/user.html.twig', [
                'mesProduits' => $mesProduits,
                'mesProduitsActifs' => $mesProduitsActifs,
            ]);
        }
    }
}