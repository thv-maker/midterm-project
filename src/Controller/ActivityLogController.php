<?php
// src/Controller/ActivityLogController.php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard/activity-logs')]
#[IsGranted('ROLE_ADMIN')]
class ActivityLogController extends AbstractController
{
    #[Route('/', name: 'app_activity_log_index', methods: ['GET'])]
    public function index(ActivityLogRepository $activityLogRepository): Response
    {
        $logs = $activityLogRepository->findRecent(100);
        $statistics = $activityLogRepository->getStatistics();

        return $this->render('activity_log/index.html.twig', [
            'logs' => $logs,
            'statistics' => $statistics,
        ]);
    }

    #[Route('/user/{userId}', name: 'app_activity_log_by_user', methods: ['GET'])]
    public function byUser(int $userId, ActivityLogRepository $activityLogRepository): Response
    {
        $logs = $activityLogRepository->findByUserId($userId);

        return $this->render('activity_log/by_user.html.twig', [
            'logs' => $logs,
            'userId' => $userId,
        ]);
    }

    #[Route('/action/{action}', name: 'app_activity_log_by_action', methods: ['GET'])]
    public function byAction(string $action, ActivityLogRepository $activityLogRepository): Response
    {
        $logs = $activityLogRepository->findByAction(strtoupper($action));

        return $this->render('activity_log/by_action.html.twig', [
            'logs' => $logs,
            'action' => $action,
        ]);
    }
}