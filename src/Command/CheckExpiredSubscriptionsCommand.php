<?php

namespace App\Command;

use App\Service\SubscriptionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-expired-subscriptions',
    description: 'Vérifie et désactive les abonnements expirés',
)]
class CheckExpiredSubscriptionsCommand extends Command
{
    private SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Vérification des abonnements expirés');

        $deactivatedCount = $this->subscriptionService->deactivateExpiredSubscriptions();

        if ($deactivatedCount > 0) {
            $io->success(sprintf('%d abonnement(s) expiré(s) ont été désactivés.', $deactivatedCount));
        } else {
            $io->info('Aucun abonnement expiré trouvé.');
        }

        return Command::SUCCESS;
    }
}