<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-user-roles',
    description: 'Ajoute le rôle ROLE_USER aux utilisateurs qui n\'ont pas de rôles assignés',
)]
class FixUserRolesCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $users = $this->userRepository->findAll();
        $updatedCount = 0;

        foreach ($users as $user) {
            $roles = $user->getRoles();
            // Si l'utilisateur n'a que ROLE_USER (ajouté automatiquement par getRoles())
            if ($roles === ['ROLE_USER']) {
                $user->setRoles(['ROLE_USER']); // Persiste explicitement ROLE_USER
                $updatedCount++;
            }
        }

        if ($updatedCount > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('%d utilisateur(s) ont été mis à jour avec le rôle ROLE_USER.', $updatedCount));
        } else {
            $io->info('Aucun utilisateur n\'avait besoin d\'être mis à jour.');
        }

        return Command::SUCCESS;
    }
}
