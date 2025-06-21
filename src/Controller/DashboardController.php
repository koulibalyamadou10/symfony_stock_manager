<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use App\Repository\CategorieRepository;
use App\Repository\UserRepository;
use App\Repository\AbonnementRepository;
use App\Service\StockAlertService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        ProduitRepository $produitRepository,
        CategorieRepository $categorieRepository,
        UserRepository $userRepository,
        AbonnementRepository $abonnementRepository,
        StockAlertService $stockAlertService
    ): Response {
        $user = $this->getUser();
        
        if ($this->isGranted('ROLE_ADMIN')) {
            // Statistiques pour l'admin
            $totalProduits = count($produitRepository->findAll());
            $produitsActifs = count($produitRepository->findActifs());
            $totalCategories = count($categorieRepository->findAll());
            $totalUtilisateurs = count($userRepository->findAll());
            $ruptureStock = $produitRepository->findRuptureStock();
            $produitsStockCritique = $stockAlertService->getProduitsStockCritique();
            
            // Statistiques des abonnements
            $abonnementsActifs = $abonnementRepository->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->where('a.estActif = :actif')
                ->andWhere('a.dateFin > :now')
                ->setParameter('actif', true)
                ->setParameter('now', new \DateTime())
                ->getQuery()
                ->getSingleScalarResult();

            return $this->render('dashboard/admin.html.twig', [
                'totalProduits' => $totalProduits,
                'produitsActifs' => $produitsActifs,
                'totalCategories' => $totalCategories,
                'totalUtilisateurs' => $totalUtilisateurs,
                'ruptureStock' => $ruptureStock,
                'produitsStockCritique' => $produitsStockCritique,
                'abonnementsActifs' => $abonnementsActifs,
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