<?php
// src/Controller/Api/ApiCustomerAuthController.php

namespace App\Controller\Api;

use App\Entity\Customer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ApiCustomerAuthController extends AbstractController
{
    #[Route('/api/customer/login', name: 'api_customer_login', methods: ['POST'])]
    public function login(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return $this->json(['error' => 'Email and password are required.'], 400);
        }

        $customer = $em->getRepository(Customer::class)->findOneBy(['email' => $email]);
        if (!$customer) {
            return $this->json(['error' => 'Invalid credentials.'], 401);
        }

        if (!password_verify($password, $customer->getPassword())) {
            return $this->json(['error' => 'Invalid credentials.'], 401);
        }

        // You can add token generation here if needed
        return $this->json([
            'success' => true,
            'customer' => [
                'id' => $customer->getId(),
                'email' => $customer->getEmail(),
                'name' => $customer->getName(),
            ],
        ]);
    }
}
