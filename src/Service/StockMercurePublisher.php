<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\Stock;

class StockMercurePublisher
{
    public const TOPIC = '/stocks';

    public function __construct(
        private WebSocketPublisher $webSocketPublisher,
        private ProductMercurePublisher $productPublisher,
    ) {}

    public function publishCreated(Stock $stock): void
    {
        $this->publish($stock, 'created');
    }

    public function publishUpdated(Stock $stock): void
    {
        $this->publish($stock, 'updated');
    }

    public function publishDeleted(int $stockId, ?int $productId = null): void
    {
        $this->publishPayload([
            'type' => 'deleted',
            'stockId' => $stockId,
            'productId' => $productId,
        ]);
    }

    public function publishForOrder(Order $order): void
    {
        $publishedProducts = [];

        foreach ($order->getOrderItems() as $orderItem) {
            $product = $orderItem->getProduct();
            if (!$product) {
                continue;
            }

            foreach ($product->getStocks() as $stock) {
                if ($stock->getId()) {
                    $this->publishUpdated($stock);
                }
            }

            $productId = $product->getId();
            if ($productId && !isset($publishedProducts[$productId])) {
                $this->productPublisher->publishUpdated($product);
                $publishedProducts[$productId] = true;
            }
        }
    }

    private function publish(Stock $stock, string $type): void
    {
        $product = $stock->getProduct();
        $lastUpdated = $stock->getLastUpdated();

        $this->publishPayload([
            'type' => $type,
            'stockId' => $stock->getId(),
            'productId' => $product?->getId(),
            'productName' => $product?->getName(),
            'quantity' => $stock->getQuantity(),
            'reorderLevel' => $stock->getReorderLevel(),
            'updatedAt' => $lastUpdated?->format(DATE_ATOM),
        ]);
    }

    private function publishPayload(array $payload): void
    {
        $this->webSocketPublisher->publish(self::TOPIC, $payload);
    }
}
