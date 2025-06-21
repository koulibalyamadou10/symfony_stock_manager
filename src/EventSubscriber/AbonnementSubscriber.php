<?php

namespace App\EventSubscriber;

use App\Repository\AbonnementRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AbonnementSubscriber implements EventSubscriberInterface
{
    private const ROUTES_PUBLIQUES = [
        'app_login',
        'app_logout',
        'app_register',
        'app_abonnement_status',
        'app_abonnement_souscrire',
        'app_abonnement_confirmation',
        'app_abonnement_callback',
    ];

    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private AbonnementRepository $abonnementRepository,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20], // Priorité plus haute que le firewall
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Ignorer les routes publiques et les ressources statiques
        if ($route === null || in_array($route, self::ROUTES_PUBLIQUES) || str_starts_with($route, '_')) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return;
        }

        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return;
        }

        // Vérifier si l'utilisateur est un admin (les admins n'ont pas besoin d'abonnement)
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return;
        }

        // Vérifier si l'utilisateur a un abonnement actif
        if (!$this->abonnementRepository->hasAbonnementValide($user)) {
            // Rediriger vers la page de statut d'abonnement
            $event->setResponse(new RedirectResponse(
                $this->urlGenerator->generate('app_abonnement_status')
            ));
        }
    }
}
