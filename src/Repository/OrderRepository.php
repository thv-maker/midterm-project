<?php
// src/Repository/OrderRepository.php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * Get total revenue from all orders
     */
    public function getTotalRevenue(): float
    {
        $result = $this->createQueryBuilder('o')
            ->select('SUM(o.total) as totalRevenue')
            ->where('o.status != :cancelled')
            ->setParameter('cancelled', 'cancelled')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }

    /**
     * Get today's revenue
     */
    public function getTodayRevenue(): float
    {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');

        $result = $this->createQueryBuilder('o')
            ->select('SUM(o.total) as todayRevenue')
            ->where('o.createdAt >= :start')
            ->andWhere('o.createdAt < :end')
            ->andWhere('o.status != :cancelled')
            ->setParameter('start', $today)
            ->setParameter('end', $tomorrow)
            ->setParameter('cancelled', 'cancelled')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }

    /**
     * Get yesterday's revenue
     */
    public function getYesterdayRevenue(): float
    {
        $yesterday = new \DateTime('yesterday');
        $today = new \DateTime('today');

        $result = $this->createQueryBuilder('o')
            ->select('SUM(o.total) as yesterdayRevenue')
            ->where('o.createdAt >= :start')
            ->andWhere('o.createdAt < :end')
            ->andWhere('o.status != :cancelled')
            ->setParameter('start', $yesterday)
            ->setParameter('end', $today)
            ->setParameter('cancelled', 'cancelled')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }

    /**
     * Calculate percentage change between two values
     */
    public function calculatePercentageChange(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * Get sales data for the last 7 days
     * Returns array with days and total revenue per day
     */
    public function getSalesDataLast7Days(): array
    {
        $endDate = new \DateTime('today');
        $startDate = new \DateTime('7 days ago');
        $startDate->setTime(0, 0, 0);

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('date', 'date');
        $rsm->addScalarResult('total', 'total');

        $query = $this->getEntityManager()->createNativeQuery('
            SELECT DATE(o.created_at) as date, SUM(o.total) as total
            FROM `order` o
            WHERE o.created_at >= :startDate
            AND o.created_at <= :endDate
            AND o.status != :cancelled
            GROUP BY DATE(o.created_at)
            ORDER BY date ASC
        ', $rsm);

        $query->setParameter('startDate', $startDate);
        $query->setParameter('endDate', $endDate);
        $query->setParameter('cancelled', 'cancelled');

        $results = $query->getResult();

        // Create array for all 7 days
        $salesData = [];
        $currentDate = clone $startDate;
        
        for ($i = 0; $i < 7; $i++) {
            $dateString = $currentDate->format('Y-m-d');
            $dayName = $currentDate->format('D'); // Mon, Tue, etc.
            
            // Find matching result
            $dayTotal = 0;
            foreach ($results as $result) {
                // Convert string date to DateTime object for comparison
                $resultDateString = is_string($result['date']) 
                    ? $result['date'] 
                    : $result['date']->format('Y-m-d');
                
                if ($resultDateString === $dateString) {
                    $dayTotal = (float) $result['total'];
                    break;
                }
            }
            
            $salesData[] = [
                'date' => $dateString,
                'day' => $dayName,
                'total' => $dayTotal
            ];
            
            $currentDate->modify('+1 day');
        }

        return $salesData;
    }

    /**
     * Get recent orders with optional limit
     */
    public function getRecentOrders(int $limit = 10): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.customer', 'c')
            ->addSelect('c')
            ->where('o.status != :cancelled')
            ->setParameter('cancelled', 'cancelled')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    // Optional: You might also want this method for DashboardController
    public function countTodayOrders(): int
    {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');

        return $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.createdAt >= :start')
            ->andWhere('o.createdAt < :end')
            ->andWhere('o.status != :cancelled')
            ->setParameter('start', $today)
            ->setParameter('end', $tomorrow)
            ->setParameter('cancelled', 'cancelled')
            ->getQuery()
            ->getSingleScalarResult();
    }

    // Optional: Method to get revenue change percentage
    public function getRevenueChangePercentage(): array
    {
        $todayRevenue = $this->getTodayRevenue();
        $yesterdayRevenue = $this->getYesterdayRevenue();
        
        $percentageChange = $this->calculatePercentageChange($todayRevenue, $yesterdayRevenue);
        
        return [
            'today' => $todayRevenue,
            'yesterday' => $yesterdayRevenue,
            'change' => $percentageChange,
            'isIncrease' => $percentageChange >= 0
        ];
    }
}