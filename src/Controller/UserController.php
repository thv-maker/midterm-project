<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\ActivityLoggerService;
use App\Service\UserMercurePublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/dashboard/users')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/row/{id}', name: 'app_user_row', methods: ['GET'])]
    public function row(User $user): Response
    {
        return $this->render('user/_row.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ActivityLoggerService $activityLogger,
        UserMercurePublisher $userPublisher
    ): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $activityLogger->logUserCreate($user);
            $userPublisher->publishCreated($user);

            $this->addFlash('success', 'User created successfully!');

            return $this->redirectToRoute('app_user_index');
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
    public function edit(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ActivityLoggerService $activityLogger,
        UserMercurePublisher $userPublisher
    ): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Only update password if a new one was provided
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $entityManager->flush();

            $activityLogger->logUserUpdate($user);
            $userPublisher->publishUpdated($user);

            $this->addFlash('success', 'User updated successfully!');

            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/toggle-status', name: 'app_user_toggle_status', methods: ['POST'])]
    public function toggleStatus(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        ActivityLoggerService $activityLogger,
        UserMercurePublisher $userPublisher
    ): Response
    {
        if ($this->isCsrfTokenValid('toggle'.$user->getId(), $request->request->get('_token'))) {
            // Toggle the user's active status
            $user->setIsActive(!$user->isActive());
            $entityManager->flush();

            $activityLogger->logUserUpdate($user);
            $userPublisher->publishUpdated($user);

            $this->addFlash('success', 'User status updated successfully!');
        }

        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        ActivityLoggerService $activityLogger,
        UserMercurePublisher $userPublisher
    ): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $userId = $user->getId();
            $userEmail = $user->getEmail();

            $entityManager->remove($user);
            $entityManager->flush();

            if ($userId) {
                $activityLogger->logUserDelete($userId, (string) $userEmail);
                $userPublisher->publishDeleted($userId, $userEmail);
            }

            $this->addFlash('success', 'User deleted successfully!');
        }

        return $this->redirectToRoute('app_user_index');
    }
}