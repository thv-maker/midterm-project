<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class OrderPushNotifier
{
    public function __construct(
        private FcmNotificationService $fcm,
        private EntityManagerInterface $entityManager,
    ) {}

    public function notifyOrderCreated(Order $order): void
    {
        $customer = $order->getCustomer();
        if (!$customer instanceof Customer) {
            return;
        }

        $orderRef = $this->formatOrderRef($order);

        $this->sendToCustomer(
            $customer,
            'Order Placed',
            sprintf('Your order %s has been received.', $orderRef),
            [
                'type' => 'order_created',
                'order_id' => (string) $order->getId(),
                'order_number' => (string) $order->getOrderNumber(),
                'status' => (string) $order->getStatus(),
            ],
        );
    }

    public function notifyOrderStatusChanged(Order $order, string $previousStatus): void
    {
        $customer = $order->getCustomer();
        if (!$customer instanceof Customer) {
            return;
        }

        $status = (string) $order->getStatus();
        if ($status === $previousStatus) {
            return;
        }

        $orderRef = $this->formatOrderRef($order);
        $statusLabel = match ($status) {
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            'pending' => 'pending',
            default => $status,
        };

        $this->sendToCustomer(
            $customer,
            'Order Update',
            sprintf('%s is now %s.', $orderRef, $statusLabel),
            [
                'type' => 'order_status_changed',
                'order_id' => (string) $order->getId(),
                'order_number' => (string) $order->getOrderNumber(),
                'status' => $status,
                'previous_status' => $previousStatus,
            ],
        );
    }

    public function storeTokenForCustomer(Customer $customer, string $token): void
    {
        $customer->setFcmToken($token);

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $customer->getEmail()]);
        if ($user instanceof User) {
            $user->setFcmToken($token);
        }
    }

    private function sendToCustomer(Customer $customer, string $title, string $body, array $data): void
    {
        $token = $customer->getFcmToken();
        if (!$token) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $customer->getEmail()]);
            $token = $user?->getFcmToken();
        }

        if (!$token) {
            return;
        }

        $this->fcm->send($token, $title, $body, $data);
    }

    private function formatOrderRef(Order $order): string
    {
        $orderNumber = $order->getOrderNumber();
        if (is_string($orderNumber) && $orderNumber !== '') {
            return '#' . $orderNumber;
        }

        return '#' . $order->getId();
    }
}
