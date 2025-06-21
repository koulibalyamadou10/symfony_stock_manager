<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Twig\Environment;

class EmailService
{
    private MailerInterface $mailer;
    private string $senderEmail;
    private string $senderName;

    public function __construct(MailerInterface $mailer, private Environment $twig)
    {
        $this->mailer = $mailer;
        $this->senderEmail = 'contact@morykoulibaly.me';
        $this->senderName = 'Gestion de Stock';
    }

    public function sendWelcomeEmail(string $to, string $name): void
    {
        $email = (new Email())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to($to)
            ->subject('Bienvenue sur l\'application de Gestion de Stock')
            ->html($this->getWelcomeTemplate($name));

        $this->mailer->send($email);
    }

    public function sendPasswordResetEmail(string $to, string $resetLink): void
    {
        $email = (new Email())
            ->from(new Address($this->senderEmail, $this->senderName))
            ->to($to)
            ->subject('Réinitialisation de votre mot de passe')
            ->html($this->getPasswordResetTemplate($resetLink));

        $this->mailer->send($email);
    }

    private function getWelcomeTemplate(string $name): string
    {
        return "
            <h1>Bienvenue {$name} !</h1>
            <p>Votre compte a été créé avec succès sur notre application de gestion de stock.</p>
            <p>Vous pouvez maintenant vous connecter et commencer à utiliser l'application.</p>
            <p>Cordialement,<br>L'équipe de Gestion de Stock</p>
        ";
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
    }
}
