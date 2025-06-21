<?php

namespace App\Controller;

use App\Entity\Abonnement;
use App\Service\LengoPayService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/abonnement')]
class AbonnementController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LengoPayService $lengoPayService
    ) {}

    #[Route('/', name: 'app_abonnement_status')]
    #[IsGranted('ROLE_USER')]
    public function status(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $abonnement = $this->entityManager->getRepository(Abonnement::class)
            ->findAbonnementActif($user);

        return $this->render('abonnement/status.html.twig', [
            'abonnement' => $abonnement,
        ]);
    }

    #[Route('/souscrire', name: 'app_abonnement_souscrire')]
    #[IsGranted('ROLE_USER')]
    public function souscrire(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Vérifier si l'utilisateur n'a pas déjà un abonnement actif
        if ($user->hasAbonnementActif()) {
            $this->addFlash('warning', 'Vous avez déjà un abonnement actif.');
            return $this->redirectToRoute('app_abonnement_status');
        }

        // Initier le paiement via Lengo Pay
        $result = $this->lengoPayService->initierPaiementAbonnement($user);

        if (!$result['success']) {
            $this->addFlash('error', 'Une erreur est survenue lors de l\'initialisation du paiement.');
            return $this->redirectToRoute('app_abonnement_status');
        }

        // Rediriger vers la page de paiement Lengo Pay
        return $this->redirect($result['payment_url']);
    }

    #[Route('/confirmation', name: 'app_abonnement_confirmation')]
    #[IsGranted('ROLE_USER')]
    public function confirmation(): Response
    {
        return $this->render('abonnement/confirmation.html.twig');
    }

    #[Route('/callback', name: 'app_abonnement_callback', methods: ['POST'])]
    public function callback(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if ($this->lengoPayService->traiterCallback($data)) {
            return new Response('OK', Response::HTTP_OK);
        }

        return new Response('Error', Response::HTTP_BAD_REQUEST);
    }

    #[Route('/historique', name: 'app_abonnement_historique')]
    #[IsGranted('ROLE_USER')]
    public function historique(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $abonnements = $user->getAbonnements();

        return $this->render('abonnement/historique.html.twig', [
            'abonnements' => $abonnements,
        ]);
    }
}
