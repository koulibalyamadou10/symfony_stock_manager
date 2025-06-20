<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $userPasswordHasher,
        EmailService $emailService
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Générer un mot de passe temporaire
            $motDePasseTemporaire = $this->genererMotDePasseTemporaire();
            
            // Hasher le mot de passe
            $user->setPassword($userPasswordHasher->hashPassword($user, $motDePasseTemporaire));

            // S'assurer qu'il y a au moins ROLE_USER
            $roles = $user->getRoles();
            if (empty($roles) || $roles === ['ROLE_USER']) {
                $user->setRoles(['ROLE_USER']);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            // Envoyer les identifiants par email
            try {
                $emailService->envoyerCredentiels($user, $motDePasseTemporaire);
                $this->addFlash('success', 'L\'utilisateur a été créé avec succès. Ses identifiants lui ont été envoyés par email.');
            } catch (\Exception $e) {
                $this->addFlash('success', 'L\'utilisateur a été créé avec succès.');
                $this->addFlash('warning', 'L\'email avec les identifiants n\'a pas pu être envoyé. Mot de passe temporaire : ' . $motDePasseTemporaire);
            }

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // S'assurer qu'il y a au moins ROLE_USER
            $roles = $user->getRoles();
            if (empty($roles)) {
                $user->setRoles(['ROLE_USER']);
            }

            $entityManager->flush();

            $this->addFlash('success', 'L\'utilisateur a été modifié avec succès.');

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            // Empêcher la suppression de son propre compte
            if ($user === $this->getUser()) {
                $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
                return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
            }

            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'L\'utilisateur a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Génère un mot de passe temporaire sécurisé
     */
    private function genererMotDePasseTemporaire(): string
    {
        $caracteres = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
        $motDePasse = '';
        
        for ($i = 0; $i < 12; $i++) {
            $motDePasse .= $caracteres[random_int(0, strlen($caracteres) - 1)];
        }
        
        return $motDePasse;
    }
}