<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CustomersController extends AbstractController
{
    #[Route('/dashboard/customers', name: 'app_dashboard_customers')]
    #[IsGranted('ROLE_STAFF')]
    public function index(): Response
    {
        return $this->render('customers/index.html.twig', [
            'controller_name' => 'CustomersController',
        ]);
    }
    
    // Optional: Keep the original route too
    #[Route('/customers', name: 'app_customers')]
    #[IsGranted('ROLE_STAFF')]
    public function customers(): Response
    {
        return $this->redirectToRoute('app_dashboard_customers');
    }
}