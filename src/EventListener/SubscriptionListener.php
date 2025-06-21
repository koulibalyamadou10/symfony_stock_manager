<?php

namespace App\EventListener;

use App\Service\SubscriptionService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
class SubscriptionListener
{
    private SubscriptionService $subscriptionService;
    private UrlGeneratorInterface $urlGenerator;
    private Security $security;

    // Routes qui ne nécessitent pas d'abonnement
    private array $exemptRoutes = [
        'app_login',
        'app_logout',
        'app_register',
        'app_subscription_status',
        'app_subscription_pay',
        'app_subscription_success',
        'app_subscription_callback',
        '_wdt',
        '_profiler',
        '_error'
    ];

    public function __construct(
        SubscriptionService $subscriptionService,
        UrlGeneratorInterface $urlGenerator,
        Security $security
    ) {
        $this->subscriptionService = $subscriptionService;
        $this->urlGenerator = $urlGenerator;
        $this->security = $security;
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Ignorer les routes exemptées
        if (in_array($route, $this->exemptRoutes) || str_starts_with($route, '_')) {
            return;
        }

        // Ignorer si l'utilisateur n'est pas connecté
        $user = $this->security->getUser();
        if (!$user) {
            return;
        }

        // Les administrateurs sont exemptés de l'abonnement
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        // Vérifier l'abonnement
        if (!$this->subscriptionService->hasActiveSubscription($user)) {
            $subscriptionUrl = $this->urlGenerator->generate('app_subscription_status');
            $response = new RedirectResponse($subscriptionUrl);
            $event->setResponse($response);
        }
    }
}