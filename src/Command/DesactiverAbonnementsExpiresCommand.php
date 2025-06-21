<?php

namespace App\Command;

use App\Service\AbonnementNotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:desactiver-abonnements-expires',
    description: 'Désactive les abonnements expirés et envoie des notifications',
)]
class DesactiverAbonnementsExpiresCommand extends Command
{
    public function __construct(
        private AbonnementNotificationService $notificationService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Vérification et mise à jour des abonnements');

        try {
            $stats = $this->notificationService->verifierEtMettreAJourAbonnements();
            
            // Afficher les résultats
            if ($stats['expires'] > 0) {
                $io->success(sprintf('%d abonnement(s) expiré(s) désactivé(s).', $stats['expires']));
            } else {
                $io->info('Aucun abonnement expiré trouvé.');
            }

            if ($stats['notifications_envoyees'] > 0) {
                $io->success(sprintf('%d notification(s) d\'expiration envoyée(s).', $stats['notifications_envoyees']));
            } else {
                $io->info('Aucune notification d\'expiration à envoyer.');
            }

            if ($stats['erreurs'] > 0) {
                $io->warning(sprintf('%d erreur(s) rencontrée(s) lors du traitement.', $stats['erreurs']));
            }

            // Afficher les statistiques générales
            $statistiques = $this->notificationService->getStatistiquesAbonnements();
            $io->section('Statistiques des abonnements');
            $io->table(
                ['Métrique', 'Valeur'],
                [
                    ['Abonnements actifs', $statistiques['abonnements_actifs']],
                    ['Expirés ce mois', $statistiques['expires_ce_mois']],
                    ['Revenus du mois', $statistiques['revenus_mois_formate']],
                ]
            );

        } catch (\Exception $e) {
            $io->error('Erreur lors de la vérification des abonnements: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
