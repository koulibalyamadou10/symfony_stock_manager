<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Abonnement;
use App\Repository\AbonnementRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AbonnementNotificationService
{
    public function __construct(
        private AbonnementRepository $abonnementRepository,
        private EmailService $emailService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    /**
     * Envoie des notifications pour les abonnements qui expirent bientôt
     */
    public function envoyerNotificationsExpiration(): int
    {
        $abonnements = $this->abonnementRepository->findAbonnementsExpirantBientot();
        $notificationsEnvoyees = 0;

        foreach ($abonnements as $abonnement) {
            try {
                $this->emailService->sendExpirationNotification(
                    $abonnement->getUtilisateur()->getEmail(),
                    $abonnement->getUtilisateur()->getNom(),
                    $abonnement->getDateFin()
                );

                $notificationsEnvoyees++;
                
                $this->logger->info('Notification d\'expiration envoyée', [
                    'user_id' => $abonnement->getUtilisateur()->getId(),
                    'email' => $abonnement->getUtilisateur()->getEmail(),
                    'date_expiration' => $abonnement->getDateFin()->format('Y-m-d H:i:s')
                ]);

            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de l\'envoi de notification d\'expiration', [
                    'user_id' => $abonnement->getUtilisateur()->getId(),
                    'email' => $abonnement->getUtilisateur()->getEmail(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $notificationsEnvoyees;
    }

    /**
     * Envoie une notification de confirmation d'abonnement
     */
    public function envoyerConfirmationAbonnement(Abonnement $abonnement): void
    {
        try {
            $htmlContent = $this->getConfirmationTemplate($abonnement);
            
            // Utiliser le service email existant ou créer une nouvelle méthode
            $this->emailService->sendWelcomeEmail(
                $abonnement->getUtilisateur()->getEmail(),
                $abonnement->getUtilisateur()->getNom()
            );

            $this->logger->info('Confirmation d\'abonnement envoyée', [
                'user_id' => $abonnement->getUtilisateur()->getId(),
                'abonnement_id' => $abonnement->getId()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de confirmation d\'abonnement', [
                'user_id' => $abonnement->getUtilisateur()->getId(),
                'abonnement_id' => $abonnement->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Vérifie le statut des abonnements et effectue les actions nécessaires
     */
    public function verifierEtMettreAJourAbonnements(): array
    {
        $stats = [
            'expires' => 0,
            'notifications_envoyees' => 0,
            'erreurs' => 0
        ];

        // Désactiver les abonnements expirés
        $stats['expires'] = $this->abonnementRepository->desactiverAbonnementsExpires();

        // Envoyer les notifications d'expiration
        try {
            $stats['notifications_envoyees'] = $this->envoyerNotificationsExpiration();
        } catch (\Exception $e) {
            $stats['erreurs']++;
            $this->logger->error('Erreur lors de l\'envoi des notifications', [
                'error' => $e->getMessage()
            ]);
        }

        return $stats;
    }

    /**
     * Génère le template de confirmation d'abonnement
     */
    private function getConfirmationTemplate(Abonnement $abonnement): string
    {
        return sprintf(
            '<h1>Confirmation de votre abonnement</h1>
            <p>Bonjour %s,</p>
            <p>Votre abonnement mensuel a été activé avec succès !</p>
            <p><strong>Détails de l\'abonnement :</strong></p>
            <ul>
                <li>Date de début : %s</li>
                <li>Date de fin : %s</li>
                <li>Montant : %s GNF</li>
                <li>Transaction ID : %s</li>
            </ul>
            <p>Vous pouvez maintenant accéder à toutes les fonctionnalités de l\'application.</p>
            <p>Cordialement,<br>L\'équipe de Gestion de Stock</p>',
            $abonnement->getUtilisateur()->getNom(),
            $abonnement->getDateDebut()->format('d/m/Y'),
            $abonnement->getDateFin()->format('d/m/Y'),
            number_format($abonnement->getMontant(), 0, ',', ' '),
            $abonnement->getTransactionId()
        );
    }

    /**
     * Récupère les statistiques des abonnements
     */
    public function getStatistiquesAbonnements(): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        // Abonnements actifs
        $abonnementsActifs = $qb->select('COUNT(a.id)')
            ->from(Abonnement::class, 'a')
            ->where('a.estActif = :actif')
            ->andWhere('a.dateFin > :now')
            ->setParameter('actif', true)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();

        // Abonnements expirés ce mois
        $debutMois = new \DateTime('first day of this month');
        $finMois = new \DateTime('last day of this month');
        
        $abonnementsExpiresCeMois = $qb->select('COUNT(a.id)')
            ->from(Abonnement::class, 'a')
            ->where('a.dateFin BETWEEN :debut AND :fin')
            ->andWhere('a.estActif = :inactif')
            ->setParameter('debut', $debutMois)
            ->setParameter('fin', $finMois)
            ->setParameter('inactif', false)
            ->getQuery()
            ->getSingleScalarResult();

        // Revenus du mois
        $revenusMois = $qb->select('SUM(a.montant)')
            ->from(Abonnement::class, 'a')
            ->where('a.dateDebut BETWEEN :debut AND :fin')
            ->setParameter('debut', $debutMois)
            ->setParameter('fin', $finMois)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        return [
            'abonnements_actifs' => $abonnementsActifs,
            'expires_ce_mois' => $abonnementsExpiresCeMois,
            'revenus_mois' => $revenusMois,
            'revenus_mois_formate' => number_format($revenusMois, 0, ',', ' ') . ' GNF'
        ];
    }
}
