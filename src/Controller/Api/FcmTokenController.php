<?php
// src/Controller/Api/FcmTokenController.php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class FcmTokenController extends AbstractController
{
    #[Route('/api/fcm-token', name: 'api_fcm_token', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function updateFcmToken(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $fcmToken = $data['fcmToken'] ?? null;

        if (!$fcmToken) {
            return $this->json(['error' => 'FCM token is required'], 400);
        }

        $user->setFcmToken($fcmToken);
        $em->persist($user);
        $em->flush();

        return $this->json(['success' => true]);
    }
}
