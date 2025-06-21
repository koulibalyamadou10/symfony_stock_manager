<?php

namespace App\Command;

use App\Service\SubscriptionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-expiration-reminders',
    description: 'Envoie des rappels d\'expiration d\'abonnement',
)]
class SendExpirationRemindersCommand extends Command
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

        $io->title('Envoi des rappels d\'expiration');

        $sentCount = $this->subscriptionService->sendExpirationReminders();

        if ($sentCount > 0) {
            $io->success(sprintf('%d rappel(s) d\'expiration ont été envoyés.', $sentCount));
        } else {
            $io->info('Aucun rappel à envoyer.');
        }

        return Command::SUCCESS;
    }
}