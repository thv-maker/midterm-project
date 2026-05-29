<?php

namespace App\Service;

use App\Entity\Order;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class OrderMercurePublisher
{
    public const TOPIC = '/orders';

    public function __construct(
        private HubInterface $mercureHub,
    ) {}

    public function publishCreated(Order $order): void
    {
        $this->publish($order, 'created');
    }

    public function publishUpdated(Order $order): void
    {
        $this->publish($order, 'updated');
    }

    private function publish(Order $order, string $type): void
    {
        $customer = $order->getCustomer();
        $createdAt = $order->getCreatedAt();

        try {
            $update = new Update(
                self::TOPIC,
                json_encode([
                    'type' => $type,
                    'orderId' => $order->getId(),
                    'orderNumber' => $order->getOrderNumber(),
                    'customerName' => $customer?->getName() ?? 'Unknown',
                    'total' => $order->getTotal(),
                    'paymentMethod' => $order->getPaymentMethod() ?? 'cash',
                    'status' => $order->getStatus(),
                    'createdAt' => $createdAt?->format(DATE_ATOM),
                ], JSON_THROW_ON_ERROR),
            );
            $this->mercureHub->publish($update);
        } catch (\Throwable) {
            // Real-time updates are best-effort; order creation must still succeed.
        }
    }
}
