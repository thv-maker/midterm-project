<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // If user is already logged in, redirect based on role
        if ($this->getUser()) {
            // Admin and Staff go to dashboard
            if ($this->isGranted('ROLE_STAFF')) {
                return $this->redirectToRoute('app_dashboard_index');
            }
            
            // Customer goes to profile (temporary fix)
            return $this->redirectToRoute('app_profile');
        }

        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // This method can be blank
        // Symfony will intercept this request and handle logout automatically
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}