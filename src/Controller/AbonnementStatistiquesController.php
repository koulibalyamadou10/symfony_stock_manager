<?php

namespace App\Controller;

use App\Service\AbonnementNotificationService;
use App\Repository\AbonnementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AbonnementStatistiquesController extends AbstractController
{
    public function __construct(
        private AbonnementNotificationService $notificationService,
        private AbonnementRepository $abonnementRepository
    ) {}

    #[Route('/admin/abonnements/statistiques', name: 'app_abonnement_statistiques')]
    public function index(): Response
    {
        // Récupérer les statistiques générales
        $stats = $this->notificationService->getStatistiquesAbonnements();

        // Récupérer les données pour le graphique (6 derniers mois)
        $dates = [];
        $abonnementsData = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $date = new \DateTime("first day of -$i month");
            $dates[] = $date->format('M Y');
            
            $abonnementsData[] = $this->abonnementRepository->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->where('a.estActif = :actif')
                ->andWhere('a.dateDebut <= :fin')
                ->andWhere('a.dateFin >= :debut')
                ->setParameter('actif', true)
                ->setParameter('debut', $date->format('Y-m-01'))
                ->setParameter('fin', $date->format('Y-m-t'))
                ->getQuery()
                ->getSingleScalarResult();
        }

        // Récupérer les 10 derniers abonnements
        $derniersAbonnements = $this->abonnementRepository->createQueryBuilder('a')
            ->orderBy('a.dateDebut', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return $this->render('abonnement/statistiques.html.twig', [
            'stats' => $stats,
            'dates' => $dates,
            'abonnements_data' => $abonnementsData,
            'derniers_abonnements' => $derniersAbonnements,
        ]);
    }

    #[Route('/admin/abonnements/export', name: 'app_abonnement_export')]
    public function export(): Response
    {
        // Récupérer tous les abonnements
        $abonnements = $this->abonnementRepository->findAll();

        // Créer le contenu CSV
        $csvContent = "ID;Utilisateur;Email;Date début;Date fin;Montant;Statut\n";

        foreach ($abonnements as $abonnement) {
            $csvContent .= sprintf(
                "%d;%s;%s;%s;%s;%d;%s\n",
                $abonnement->getId(),
                $abonnement->getUtilisateur()->getNom(),
                $abonnement->getUtilisateur()->getEmail(),
                $abonnement->getDateDebut()->format('d/m/Y'),
                $abonnement->getDateFin()->format('d/m/Y'),
                $abonnement->getMontant(),
                $abonnement->isEstActif() ? 'Actif' : 'Inactif'
            );
        }

        // Créer la réponse
        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="abonnements.csv"');

        return $response;
    }

    #[Route('/admin/abonnements/dashboard', name: 'app_abonnement_dashboard')]
    public function dashboard(): Response
    {
        // Statistiques pour le tableau de bord
        $stats = $this->notificationService->getStatistiquesAbonnements();

        // Abonnements qui expirent bientôt
        $abonnementsExpirantBientot = $this->abonnementRepository->findAbonnementsExpirantBientot();

        return $this->render('abonnement/dashboard.html.twig', [
            'stats' => $stats,
            'abonnements_expirant' => $abonnementsExpirantBientot,
        ]);
    }
}
