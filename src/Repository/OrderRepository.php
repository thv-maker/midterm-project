<?php
// src/Repository/OrderRepository.php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
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
     * @return Order[]
     */
    public function findCreatedAfter(\DateTimeImmutable $since): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.createdAt >= :since')
            ->setParameter('since', $since)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<array{type: string, orderId: int}>
     */
    public function findFeedEventsAfter(\DateTimeImmutable $since): array
    {
        $created = $this->findCreatedAfter($since);
        $events = [];

        foreach ($created as $order) {
            $id = $order->getId();
            if ($id) {
                $events[] = ['type' => 'created', 'orderId' => $id];
            }
        }

        return $events;
    }

    /**
     * Revenue counts completed orders only (recognized sales).
     */
    private function revenueQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.status = :completed')
            ->setParameter('completed', 'completed');
    }

    /**
     * Get total revenue from completed orders.
     */
    public function getTotalRevenue(): float
    {
        $result = $this->revenueQueryBuilder()
            ->select('COALESCE(SUM(o.total), 0)')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result;
    }

    /**
     * Get today's revenue from completed orders.
     */
    public function getTodayRevenue(): float
    {
        $today = new \DateTimeImmutable('today');
        $tomorrow = new \DateTimeImmutable('tomorrow');

        $result = $this->revenueQueryBuilder()
            ->select('COALESCE(SUM(o.total), 0)')
            ->andWhere('o.createdAt >= :start')
            ->andWhere('o.createdAt < :end')
            ->setParameter('start', $today)
            ->setParameter('end', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result;
    }

    /**
     * Get yesterday's revenue from completed orders.
     */
    public function getYesterdayRevenue(): float
    {
        $yesterday = new \DateTimeImmutable('yesterday');
        $today = new \DateTimeImmutable('today');

        $result = $this->revenueQueryBuilder()
            ->select('COALESCE(SUM(o.total), 0)')
            ->andWhere('o.createdAt >= :start')
            ->andWhere('o.createdAt < :end')
            ->setParameter('start', $yesterday)
            ->setParameter('end', $today)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result;
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
     * Get sales data for the last 7 days (completed orders only).
     *
     * @return list<array{date: string, day: string, total: float}>
     */
    public function getSalesDataLast7Days(): array
    {
        $startDate = new \DateTimeImmutable('6 days ago midnight');
        $endDate = new \DateTimeImmutable('tomorrow midnight');

        /** @var Order[] $orders */
        $orders = $this->revenueQueryBuilder()
            ->andWhere('o.createdAt >= :start')
            ->andWhere('o.createdAt < :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getResult();

        $totalsByDate = [];
        foreach ($orders as $order) {
            $createdAt = $order->getCreatedAt();
            if (!$createdAt instanceof \DateTimeImmutable) {
                continue;
            }

            $dateString = $createdAt->format('Y-m-d');
            $totalsByDate[$dateString] = ($totalsByDate[$dateString] ?? 0.0) + (float) ($order->getTotal() ?? 0);
        }

        $salesData = [];
        $currentDate = $startDate;

        for ($i = 0; $i < 7; $i++) {
            $dateString = $currentDate->format('Y-m-d');
            $salesData[] = [
                'date' => $dateString,
                'day' => $currentDate->format('D'),
                'total' => $totalsByDate[$dateString] ?? 0.0,
            ];
            $currentDate = $currentDate->modify('+1 day');
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

    /**
     * Returns products ranked by total ordered quantity.
     */
    public function getTopOrderedProducts(int $limit = 5): array
    {
        return $this->createQueryBuilder('o')
            ->select('p.id AS product_id')
            ->addSelect('p.name AS product_name')
            ->addSelect('SUM(oi.quantity) AS total_quantity')
            ->addSelect('COUNT(DISTINCT c.id) AS customer_count')
            ->innerJoin('o.orderItems', 'oi')
            ->innerJoin('oi.product', 'p')
            ->innerJoin('o.customer', 'c')
            ->where('o.status != :cancelled')
            ->setParameter('cancelled', 'cancelled')
            ->groupBy('p.id, p.name')
            ->orderBy('total_quantity', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();
    }
}
