<?php

namespace App\Controller;

use App\Service\SubscriptionService;
use App\Service\LengoPayService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;

#[Route('/subscription')]
#[IsGranted('ROLE_USER')]
class SubscriptionController extends AbstractController
{
    private SubscriptionService $subscriptionService;
    private LengoPayService $lengoPayService;
    private LoggerInterface $logger;

    public function __construct(
        SubscriptionService $subscriptionService,
        LengoPayService $lengoPayService,
        LoggerInterface $logger
    ) {
        $this->subscriptionService = $subscriptionService;
        $this->lengoPayService = $lengoPayService;
        $this->logger = $logger;
    }

    #[Route('/status', name: 'app_subscription_status')]
    public function status(): Response
    {
        $user = $this->getUser();
        $subscription = $this->subscriptionService->getCurrentSubscription($user);
        $hasActiveSubscription = $this->subscriptionService->hasActiveSubscription($user);

        return $this->render('subscription/status.html.twig', [
            'subscription' => $subscription,
            'hasActiveSubscription' => $hasActiveSubscription,
            'subscriptionAmount' => 50000
        ]);
    }

    #[Route('/pay', name: 'app_subscription_pay', methods: ['POST'])]
    public function pay(): Response
    {
        $user = $this->getUser();

        // Vérifier si l'utilisateur a déjà un abonnement actif
        if ($this->subscriptionService->hasActiveSubscription($user)) {
            $this->addFlash('info', 'Vous avez déjà un abonnement actif.');
            return $this->redirectToRoute('app_dashboard');
        }

        try {
            $result = $this->subscriptionService->initiatePayment($user);

            if ($result['success']) {
                $this->logger->info('Redirection vers Lengo Pay', [
                    'user_id' => $user->getId(),
                    'payment_url' => $result['payment_url']
                ]);

                return $this->redirect($result['payment_url']);
            } else {
                $this->addFlash('error', 'Erreur lors de l\'initiation du paiement: ' . $result['error']);
                return $this->redirectToRoute('app_subscription_status');
            }
        } catch (\Exception $e) {
            $this->logger->error('Exception lors de l\'initiation du paiement', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage()
            ]);

            $this->addFlash('error', 'Une erreur technique est survenue. Veuillez réessayer.');
            return $this->redirectToRoute('app_subscription_status');
        }
    }

    #[Route('/success', name: 'app_subscription_success')]
    public function success(Request $request): Response
    {
        $paymentId = $request->query->get('pay_id');
        
        if ($paymentId) {
            $this->logger->info('Retour de paiement Lengo Pay', [
                'pay_id' => $paymentId,
                'user_id' => $this->getUser()->getId()
            ]);

            // Optionnel: vérifier le statut du paiement
            try {
                $paymentStatus = $this->lengoPayService->verifyPayment($paymentId);
                if (isset($paymentStatus['status']) && in_array($paymentStatus['status'], ['success', 'completed', 'paid'])) {
                    // Confirmer le paiement si pas déjà fait
                    $this->subscriptionService->confirmPayment($paymentId);
                }
            } catch (\Exception $e) {
                $this->logger->warning('Erreur lors de la vérification du paiement sur la page de succès', [
                    'pay_id' => $paymentId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $this->render('subscription/success.html.twig', [
            'paymentId' => $paymentId
        ]);
    }

    #[Route('/callback', name: 'app_subscription_callback', methods: ['POST'])]
    public function callback(Request $request): Response
    {
        try {
            $callbackData = json_decode($request->getContent(), true);
            
            $this->logger->info('Callback reçu de Lengo Pay', [
                'content' => $request->getContent(),
                'parsed_data' => $callbackData
            ]);

            if (!$callbackData || !isset($callbackData['pay_id'])) {
                $this->logger->error('Données de callback invalides', [
                    'content' => $request->getContent()
                ]);
                return new Response('Invalid callback data', 400);
            }

            $paymentId = $callbackData['pay_id'];
            $status = $callbackData['status'] ?? 'unknown';

            $this->logger->info('Traitement du callback', [
                'pay_id' => $paymentId,
                'status' => $status
            ]);

            // Traiter le callback avec Lengo Pay Service
            if ($this->lengoPayService->processCallback($callbackData)) {
                // Confirmer le paiement dans notre système
                $confirmed = $this->subscriptionService->confirmPayment($paymentId, $callbackData);
                
                if ($confirmed) {
                    $this->logger->info('Paiement confirmé via callback', ['pay_id' => $paymentId]);
                    return new Response('Payment confirmed', 200);
                } else {
                    $this->logger->error('Échec de la confirmation du paiement', ['pay_id' => $paymentId]);
                    return new Response('Payment confirmation failed', 500);
                }
            } else {
                $this->logger->warning('Callback non traité', [
                    'pay_id' => $paymentId,
                    'status' => $status
                ]);
                return new Response('Callback not processed', 200);
            }
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du traitement du callback', [
                'error' => $e->getMessage(),
                'content' => $request->getContent(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return new Response('Callback processing error', 500);
        }
    }

    #[Route('/cancel', name: 'app_subscription_cancel')]
    public function cancel(): Response
    {
        $this->addFlash('warning', 'Le paiement a été annulé. Vous devez avoir un abonnement actif pour utiliser l\'application.');
        
        return $this->render('subscription/cancel.html.twig');
    }
}