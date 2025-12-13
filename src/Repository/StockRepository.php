<?php

namespace App\Repository;

use App\Entity\Stock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Stock>
 */
class StockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stock::class);
    }

    /**
     * Find low stock items (quantity <= reorder level)
     */
    public function findLowStockItems(int $limit = 5): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.quantity <= s.reorderLevel')
            ->orderBy('s.lastUpdated', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count low stock items (quantity <= reorder level)
     */
    public function countLowStockItems(): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.quantity <= s.reorderLevel')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find out of stock items (quantity = 0)
     */
    public function findOutOfStockItems(int $limit = 10): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.quantity = 0')
            ->orderBy('s.lastUpdated', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get total value of all stock (quantity * price)
     */
    public function getTotalStockValue(): float
    {
        $result = $this->createQueryBuilder('s')
            ->leftJoin('s.product', 'p')
            ->select('SUM(s.quantity * p.price) as totalValue')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }

    /**
     * Find stock items that need reorder (quantity <= reorder level)
     * and haven't been reordered recently
     */
    public function findItemsNeedingReorder(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.quantity <= s.reorderLevel')
            ->andWhere('s.lastReorderDate IS NULL OR s.lastReorderDate < :weekAgo')
            ->setParameter('weekAgo', new \DateTime('-1 week'))
            ->orderBy('s.quantity', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get stock summary statistics
     */
    public function getStockSummary(): array
    {
        $qb = $this->createQueryBuilder('s');

        // Count all stock items
        $totalItems = $qb->select('COUNT(s.id)')->getQuery()->getSingleScalarResult();

        // Count low stock items
        $lowStockCount = $this->countLowStockItems();

        // Count out of stock items
        $outOfStockCount = $qb->select('COUNT(s.id)')
            ->where('s.quantity = 0')
            ->getQuery()
            ->getSingleScalarResult();

        // Get total stock value
        $totalValue = $this->getTotalStockValue();

        return [
            'totalItems' => (int) $totalItems,
            'lowStockCount' => (int) $lowStockCount,
            'outOfStockCount' => (int) $outOfStockCount,
            'totalValue' => (float) $totalValue,
        ];
    }

    /**
     * Get stock movement history (requires StockMovement entity)
     * This is optional if you have stock movement tracking
     */
    public function getRecentStockMovements(int $limit = 10): array
    {
        // If you have a StockMovement entity, you can implement this
        // For now, we'll return an empty array or you can adjust based on your needs
        return [];
    }

    /**
     * Find stock by product ID
     */
    public function findByProductId(int $productId): ?Stock
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.product = :productId')
            ->setParameter('productId', $productId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Update stock quantity
     */
    public function updateStockQuantity(int $stockId, int $quantityChange): void
    {
        $this->createQueryBuilder('s')
            ->update()
            ->set('s.quantity', 's.quantity + :change')
            ->set('s.lastUpdated', ':now')  
            ->where('s.id = :id')
            ->setParameter('change', $quantityChange)
            ->setParameter('now', new \DateTime())
            ->setParameter('id', $stockId)
            ->getQuery()
            ->execute();
    }
}