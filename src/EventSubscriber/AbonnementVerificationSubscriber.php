<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\AbonnementNotificationService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Psr\Log\LoggerInterface;

class AbonnementVerificationSubscriber implements EventSubscriberInterface
{
    private const ROUTES_AUTORISEES = [
        'app_login',
        'app_logout',
        'app_abonnement_souscrire',
        'app_abonnement_status',
        'app_abonnement_confirmation',
    ];

    public function __construct(
        private Security $security,
        private AbonnementNotificationService $notificationService,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger,
        private RequestStack $requestStack
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 31], // Priorité plus haute que le firewall
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        // Ignorer les routes autorisées et les utilisateurs admin
        if (in_array($route, self::ROUTES_AUTORISEES) || 
            !$this->security->getUser() || 
            $this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        /** @var User $user */
        $user = $this->security->getUser();

        try {
            // Vérifier si l'utilisateur a un abonnement actif
            if (!$user->hasAbonnementActif()) {
                $this->logger->info('Redirection vers la page d\'abonnement', [
                    'user_id' => $user->getId(),
                    'route_tentee' => $route
                ]);

                $event->setResponse(new RedirectResponse(
                    $this->urlGenerator->generate('app_abonnement_status')
                ));
                return;
            }

            // Vérifier si l'abonnement expire bientôt
            if ($user->hasAbonnementExpirantBientot()) {
                // Récupérer l'abonnement actif
                $abonnement = $user->getAbonnementActif();
                
                if ($abonnement) {
                    $this->logger->info('Abonnement expirant bientôt détecté', [
                        'user_id' => $user->getId(),
                        'date_expiration' => $abonnement->getDateFin()->format('Y-m-d')
                    ]);

                    // Ajouter un message flash pour informer l'utilisateur
                    /** @var Session $session */
                    $session = $this->requestStack->getSession();
                    $session->set('_flash/warning', [sprintf(
                        'Votre abonnement expire le %s. <a href="%s">Renouveler maintenant</a>',
                        $abonnement->getDateFin()->format('d/m/Y'),
                        $this->urlGenerator->generate('app_abonnement_souscrire')
                    )]);
                }
            }

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la vérification de l\'abonnement', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()
            ]);
        }
    }
}
