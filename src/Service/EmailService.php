<?php

namespace App\Service;

use App\Entity\Subscription;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Twig\Environment;
use Psr\Log\LoggerInterface;

class EmailService
{
    private MailerInterface $mailer;
    private string $senderEmail;
    private string $senderName;

    public function __construct(
        MailerInterface $mailer, 
        private Environment $twig,
        private LoggerInterface $logger
    ) {
        $this->mailer = $mailer;
        $this->senderEmail = 'noreply@gestionstock.com';
        $this->senderName = 'Gestion de Stock - UGANC';
    }

    public function sendWelcomeEmail(string $to, string $name): void
    {
        try {
            $htmlContent = $this->twig->render('emails/bienvenue.html.twig', [
                'user' => (object) ['nom' => $name, 'email' => $to]
            ]);

            $email = (new Email())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to($to)
                ->subject('Bienvenue sur l\'application de Gestion de Stock')
                ->html($htmlContent);

            $this->mailer->send($email);
            
            $this->logger->info('Email de bienvenue envoyé', [
                'to' => $to,
                'name' => $name
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de l\'email de bienvenue', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function sendPasswordResetEmail(string $to, string $resetLink): void
    {
        try {
            $email = (new Email())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to($to)
                ->subject('Réinitialisation de votre mot de passe')
                ->html($this->getPasswordResetTemplate($resetLink));

            $this->mailer->send($email);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de l\'email de réinitialisation', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function getPasswordResetTemplate(string $resetLink): string
    {
        return "
            <h1>Réinitialisation de votre mot de passe</h1>
            <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
            <p>Cliquez sur le lien ci-dessous pour définir un nouveau mot de passe :</p>
            <p><a href='{$resetLink}'>Réinitialiser mon mot de passe</a></p>
            <p>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
            <p>Cordialement,<br>L'équipe de Gestion de Stock</p>
        ";
    }

    public function envoyerCredentiels(User $user, string $motDePasse): void
    {
        try {
            $htmlContent = $this->twig->render('emails/credentials.html.twig', [
                'nom' => $user->getNom(),
                'email' => $user->getEmail(),
                'motDePasse' => $motDePasse,
            ]);

            $email = (new Email())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to($user->getEmail())
                ->subject('Vos identifiants de connexion - Gestion de Stock')
                ->html($htmlContent);

            $this->mailer->send($email);

            $this->logger->info('Identifiants envoyés par email', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi des identifiants', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function sendExpirationNotification(string $to, string $name, \DateTimeInterface $dateExpiration): void
    {
        try {
            $htmlContent = $this->twig->render('emails/expiration.html.twig', [
                'nom' => $name,
                'dateExpiration' => $dateExpiration,
            ]);

            $email = (new Email())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to($to)
                ->subject('Votre abonnement expire bientôt - Gestion de Stock')
                ->html($htmlContent);

            $this->mailer->send($email);

            $this->logger->info('Notification d\'expiration envoyée', [
                'to' => $to,
                'expiration_date' => $dateExpiration->format('Y-m-d')
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de la notification d\'expiration', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function envoyerEmailBienvenue(User $user): void
    {
        $this->sendWelcomeEmail($user->getEmail(), $user->getNom());
    }

    public function envoyerConfirmationAbonnement(User $user, Subscription $subscription): void
    {
        try {
            $htmlContent = $this->twig->render('emails/confirmation_abonnement.html.twig', [
                'user' => $user,
                'subscription' => $subscription,
            ]);

            $email = (new Email())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to($user->getEmail())
                ->subject('Confirmation de votre abonnement - Gestion de Stock')
                ->html($htmlContent);

            $this->mailer->send($email);

            $this->logger->info('Confirmation d\'abonnement envoyée', [
                'user_id' => $user->getId(),
                'subscription_id' => $subscription->getId()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de la confirmation d\'abonnement', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function envoyerRappelExpiration(User $user, Subscription $subscription): void
    {
        try {
            $htmlContent = $this->twig->render('emails/rappel_expiration.html.twig', [
                'user' => $user,
                'subscription' => $subscription,
            ]);

            $email = (new Email())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to($user->getEmail())
                ->subject('Votre abonnement expire bientôt - Gestion de Stock')
                ->html($htmlContent);

            $this->mailer->send($email);

            $this->logger->info('Rappel d\'expiration envoyé', [
                'user_id' => $user->getId(),
                'subscription_id' => $subscription->getId()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi du rappel d\'expiration', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}