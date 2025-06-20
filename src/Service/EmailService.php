<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Produit;
use App\Repository\UserRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Environment;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private ParameterBagInterface $parameterBag,
        private UserRepository $userRepository
    ) {}

    /**
     * Envoie un email de bienvenue après inscription
     */
    public function envoyerEmailBienvenue(User $user): void
    {
        $email = (new Email())
            ->from($_ENV['MAILER_FROM_EMAIL'])
            ->to($user->getEmail())
            ->subject('Bienvenue sur la plateforme de Gestion de Stock - UGANC')
            ->html($this->twig->render('emails/bienvenue.html.twig', [
                'user' => $user
            ]));

        $this->mailer->send($email);
    }

    /**
     * Envoie les identifiants de connexion à un nouvel utilisateur créé par l'admin
     */
    public function envoyerCredentiels(User $user, string $motDePasseTemporaire): void
    {
        $email = (new Email())
            ->from($_ENV['MAILER_FROM_EMAIL'])
            ->to($user->getEmail())
            ->subject('Vos identifiants de connexion - Gestion de Stock UGANC')
            ->html($this->twig->render('emails/credentials.html.twig', [
                'user' => $user,
                'motDePasse' => $motDePasseTemporaire
            ]));

        $this->mailer->send($email);
    }

    /**
     * Envoie une alerte de stock faible
     */
    public function envoyerAlerteStockFaible(array $produitsEnRupture): void
    {
        // Récupérer tous les administrateurs
        $admins = $this->userRepository->findAdministrateurs();

        foreach ($admins as $admin) {
            $email = (new Email())
                ->from($_ENV['MAILER_FROM_EMAIL'])
                ->to($admin->getEmail())
                ->subject('🚨 Alerte Stock - Produits à réapprovisionner')
                ->html($this->twig->render('emails/alerte_stock.html.twig', [
                    'admin' => $admin,
                    'produits' => $produitsEnRupture
                ]));

            $this->mailer->send($email);
        }
    }

    /**
     * Envoie une notification de vente
     */
    public function envoyerNotificationVente(Produit $produit, int $quantiteVendue, User $vendeur): void
    {
        $email = (new Email())
            ->from($_ENV['MAILER_FROM_EMAIL'])
            ->to($produit->getUtilisateur()->getEmail())
            ->subject('Vente effectuée - ' . $produit->getNom())
            ->html($this->twig->render('emails/notification_vente.html.twig', [
                'produit' => $produit,
                'quantiteVendue' => $quantiteVendue,
                'vendeur' => $vendeur
            ]));

        $this->mailer->send($email);
    }
}