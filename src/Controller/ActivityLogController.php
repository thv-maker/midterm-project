<?php
// src/Controller/ActivityLogController.php

namespace App\Controller;

use App\Entity\ActivityLog;
use App\Repository\ActivityLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
            'pageLoadedAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ]);
    }

    #[Route('/feed', name: 'app_activity_log_feed', methods: ['GET'])]
    public function feed(Request $request, ActivityLogRepository $activityLogRepository): JsonResponse
    {
        $sinceParam = $request->query->get('since');
        if (!is_string($sinceParam) || $sinceParam === '') {
            return $this->json(['logs' => []]);
        }

        try {
            $since = new \DateTimeImmutable($sinceParam);
        } catch (\Exception) {
            return $this->json(['error' => 'Invalid since timestamp.'], 400);
        }

        return $this->json([
            'logs' => $activityLogRepository->findFeedEventsAfter($since),
        ]);
    }

    #[Route('/row/{id}', name: 'app_activity_log_row', methods: ['GET'])]
    public function row(ActivityLog $log): Response
    {
        return $this->render('activity_log/_row.html.twig', [
            'log' => $log,
        ]);
    }

    #[Route('/stats', name: 'app_activity_log_stats', methods: ['GET'])]
    public function stats(ActivityLogRepository $activityLogRepository): JsonResponse
    {
        $statistics = $activityLogRepository->getStatistics();

        return $this->json([
            'total' => $statistics['total'],
            'today' => $statistics['today'],
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

        return $this->render('activity_log/byaction.html.twig', [
            'logs' => $logs,
            'action' => $action,
        ]);
    }
}