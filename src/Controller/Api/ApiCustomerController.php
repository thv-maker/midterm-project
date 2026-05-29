<?php
// src/Controller/Api/ApiCustomerController.php

namespace App\Controller\Api;

use App\Entity\Customer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ApiCustomerController extends AbstractController
{
    #[Route('/api/customer/register', name: 'api_customer_register', methods: ['POST'])]
    public function register(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $name = $data['name'] ?? null;

        if (!$email || !$password || !$name) {
            return $this->json(['error' => 'Email, password, and name are required.'], 400);
        }

        $customer = new Customer();
        $customer->setEmail($email);
        $customer->setPassword(password_hash($password, PASSWORD_BCRYPT));
        $customer->setName($name);
        $em->persist($customer);
        $em->flush();

        return $this->json(['success' => true, 'customerId' => $customer->getId()]);
    }
}
