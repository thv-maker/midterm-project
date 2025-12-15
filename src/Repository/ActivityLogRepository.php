<?php
// src/Repository/ActivityLogRepository.php

namespace App\Repository;

use App\Entity\ActivityLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityLog>
 */
class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLog::class);
    }

    /**
     * Find logs by user ID
     */
    public function findByUserId(int $userId, int $limit = 50): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('a.dateTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find logs by action type
     */
    public function findByAction(string $action, int $limit = 50): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.action = :action')
            ->setParameter('action', $action)
            ->orderBy('a.dateTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recent logs
     */
    public function findRecent(int $limit = 100): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.dateTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find logs within date range
     */
    public function findByDateRange(\DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.dateTime BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('a.dateTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count logs by action
     */
    public function countByAction(): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.action, COUNT(a.id) as count')
            ->groupBy('a.action')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('a');
        
        return [
            'total' => $qb->select('COUNT(a.id)')->getQuery()->getSingleScalarResult(),
            'today' => $this->createQueryBuilder('a')
                ->select('COUNT(a.id)')
                ->where('a.dateTime >= :today')
                ->setParameter('today', new \DateTime('today'))
                ->getQuery()
                ->getSingleScalarResult(),
            'by_action' => $this->countByAction()
        ];
    }
}