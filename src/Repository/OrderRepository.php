<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
     * Get total revenue
     */
    public function getTotalRevenue(): float
    {
        $result = $this->createQueryBuilder('o')
            ->select('SUM(o.totalAmount) as total')
            ->where('o.status != :cancelled')
            ->setParameter('cancelled', 'cancelled')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    /**
     * Get sales data grouped by date for the last 7 days
     */
    public function getSalesDataLast7Days(): array
    {
        $date = new \DateTime('-7 days');
        
        return $this->createQueryBuilder('o')
            ->select('SUBSTRING(o.orderDate, 1, 10) as date, SUM(o.totalAmount) as total, COUNT(o.id) as count')
            ->where('o.orderDate >= :date')
            ->andWhere('o.status != :cancelled')
            ->setParameter('date', $date)
            ->setParameter('cancelled', 'cancelled')
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get recent orders
     */
    public function getRecentOrders(int $limit = 5): array
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.orderDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get today's revenue
     */
    public function getTodayRevenue(): float
    {
        $startOfDay = new \DateTime('today');
        $endOfDay = new \DateTime('tomorrow');
        
        $result = $this->createQueryBuilder('o')
            ->select('SUM(o.totalAmount) as total')
            ->where('o.orderDate >= :start')
            ->andWhere('o.orderDate < :end')
            ->andWhere('o.status != :cancelled')
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay)
            ->setParameter('cancelled', 'cancelled')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    /**
     * Get yesterday's revenue for comparison
     */
    public function getYesterdayRevenue(): float
    {
        $startOfYesterday = new \DateTime('yesterday');
        $endOfYesterday = new \DateTime('today');
        
        $result = $this->createQueryBuilder('o')
            ->select('SUM(o.totalAmount) as total')
            ->where('o.orderDate >= :start')
            ->andWhere('o.orderDate < :end')
            ->andWhere('o.status != :cancelled')
            ->setParameter('start', $startOfYesterday)
            ->setParameter('end', $endOfYesterday)
            ->setParameter('cancelled', 'cancelled')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    /**
     * Calculate percentage change
     */
    public function calculatePercentageChange(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        
        return (($current - $previous) / $previous) * 100;
    }
}