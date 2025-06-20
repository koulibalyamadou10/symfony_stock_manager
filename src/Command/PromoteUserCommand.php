<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:promote-user',
    description: 'Promouvoir un utilisateur au rôle ROLE_ADMIN',
)]
class PromoteUserCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email de l\'utilisateur à promouvoir')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error(sprintf('Aucun utilisateur trouvé avec l\'email: %s', $email));
            return Command::FAILURE;
        }

        $currentRoles = $user->getRoles();
        
        if (in_array('ROLE_ADMIN', $currentRoles)) {
            $io->info(sprintf('L\'utilisateur %s (%s) est déjà administrateur.', $user->getNom(), $email));
            return Command::SUCCESS;
        }

        // Ajouter ROLE_ADMIN (ROLE_USER sera automatiquement inclus via la hiérarchie)
        $user->setRoles(['ROLE_ADMIN']);
        $this->entityManager->flush();

        $io->success(sprintf('L\'utilisateur %s (%s) a été promu administrateur avec succès!', $user->getNom(), $email));

        return Command::SUCCESS;
    }
}
