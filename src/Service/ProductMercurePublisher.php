<?php

namespace App\Service;

use App\Entity\Product;

class ProductMercurePublisher
{
    public const TOPIC = '/products';

    public function __construct(
        private WebSocketPublisher $webSocketPublisher,
    ) {}

    public function publishCreated(Product $product): void
    {
        $this->publish($product, 'created');
    }

    public function publishUpdated(Product $product): void
    {
        $this->publish($product, 'updated');
    }

    public function publishDeleted(int $productId, ?string $name): void
    {
        $this->publishPayload([
            'type' => 'deleted',
            'productId' => $productId,
            'name' => $name,
        ]);
    }

    private function publish(Product $product, string $type): void
    {
        $this->publishPayload([
            'type' => $type,
            'productId' => $product->getId(),
            'name' => $product->getName(),
            'category' => $product->getCategory(),
            'price' => $product->getPrice(),
        ]);
    }

    private function publishPayload(array $payload): void
    {
        $this->webSocketPublisher->publish(self::TOPIC, $payload);
    }
}
