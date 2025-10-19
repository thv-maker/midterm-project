<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Form\CustomerType;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dashboard/customers')]
final class CustomerController extends AbstractController
{
    #[Route('', name: 'app_dashboard_customers', methods: ['GET'])]
    public function index(CustomerRepository $customerRepository): Response
    {
        return $this->render('customer/index.html.twig', [
            'customers' => $customerRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_dashboard_customer_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $customer = new Customer();

        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Set defaults if needed
            if (!$customer->getDateJoined()) {
                $customer->setDateJoined(new \DateTime());
            }
            if (!$customer->getLoyaltyPoints()) {
                $customer->setLoyaltyPoints(0);
            }
            if (!$customer->getTotalPurchases()) {
                $customer->setTotalPurchases(0.0);
            }
            if (!$customer->getStatus()) {
                $customer->setStatus('active');
            }

            $entityManager->persist($customer);
            $entityManager->flush();

            $this->addFlash('success', 'Customer created successfully!');

            // Redirect to index to see the new customer
            return $this->redirectToRoute('app_dashboard_customers');
        }

        return $this->render('customer/new.html.twig', [
            'customer' => $customer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_dashboard_customer_show', methods: ['GET'])]
    public function show(Customer $customer): Response
    {
        return $this->render('customer/show.html.twig', [
            'customer' => $customer,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_dashboard_customer_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Customer $customer, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Customer updated successfully!');
            return $this->redirectToRoute('app_dashboard_customers');
        }

        return $this->render('customer/edit.html.twig', [
            'customer' => $customer,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_customer_delete', methods: ['POST'])]
    public function delete(Request $request, Customer $customer, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$customer->getId(), $request->request->get('_token'))) {
            $entityManager->remove($customer);
            $entityManager->flush();
            $this->addFlash('success', 'Customer deleted successfully!');
        }

        return $this->redirectToRoute('app_dashboard_customers');
    }
}
