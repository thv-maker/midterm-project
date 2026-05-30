<?php

namespace App\Service;

use App\Entity\Order;

class OrderMercurePublisher
{
    public const TOPIC = '/orders';

    public function __construct(
        private WebSocketPublisher $webSocketPublisher,
    ) {}

    public function publishCreated(Order $order): void
    {
        $this->publish($order, 'created');
    }

    public function publishUpdated(Order $order): void
    {
        $this->publish($order, 'updated');
    }

    public function publishDeleted(int $orderId, ?int $customerId = null): void
    {
        $this->publishToTopic(self::TOPIC, [
            'type' => 'deleted',
            'orderId' => $orderId,
        ]);

        if ($customerId) {
            $this->publishCustomerEvent($customerId, 'order_status_changed', [
                'orderId' => $orderId,
                'status' => 'deleted',
            ]);
        }
    }

    private function publish(Order $order, string $type): void
    {
        $customer = $order->getCustomer();
        $createdAt = $order->getCreatedAt();

        $this->publishToTopic(self::TOPIC, [
            'type' => $type,
            'orderId' => $order->getId(),
            'orderNumber' => $order->getOrderNumber(),
            'customerName' => $customer?->getName() ?? 'Unknown',
            'total' => $order->getTotal(),
            'paymentMethod' => $order->getPaymentMethod() ?? 'cash',
            'status' => $order->getStatus(),
            'createdAt' => $createdAt?->format(DATE_ATOM),
        ]);

        $customerId = $customer?->getId();
        if (!$customerId) {
            return;
        }

        $mobileType = match ($type) {
            'created' => 'order_created',
            'updated' => 'order_status_changed',
            default => 'order_update',
        };

        $this->publishCustomerEvent($customerId, $mobileType, [
            'orderId' => $order->getId(),
            'orderNumber' => $order->getOrderNumber(),
            'status' => $order->getStatus(),
            'total' => $order->getTotal(),
        ]);
    }

    private function publishCustomerEvent(int $customerId, string $type, array $payload): void
    {
        $this->publishToTopic(
            sprintf('/customer/%d/orders', $customerId),
            ['type' => $type, 'payload' => $payload],
        );
    }

    private function publishToTopic(string $topic, array $payload): void
    {
        $this->webSocketPublisher->publish($topic, $payload);
    }
}
