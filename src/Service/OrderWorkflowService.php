<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;

class OrderWorkflowService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FcmNotificationService $fcm,
    ) {}

    public function createOrder(
        Customer $customer,
        array $itemsData,
        string $paymentMethod = 'cash',
        ?string $gcashNumber = null,
        ?string $cardType = null,
    ): Order {
        $normalizedItems = $this->normalizeItems($itemsData);
        $paymentDetails = $this->normalizePaymentDetails($paymentMethod, $gcashNumber, $cardType);

        if ($normalizedItems === []) {
            throw new \InvalidArgumentException('Please select at least one product.');
        }

        if ($this->isDuplicateOrder($customer, $normalizedItems, $paymentDetails)) {
            throw new \RuntimeException('Duplicate order detected. Please wait a moment and try again.');
        }

        $order = new Order();
        $order->setOrderNumber('ORD-' . strtoupper(uniqid()));
        $order->setCustomer($customer);
        $order->setStatus('pending');
        $order->setCreatedAt(new \DateTimeImmutable());
        $order->setPaymentMethod($paymentDetails['payment_method']);
        $order->setGcashNumber($paymentDetails['gcash_number']);
        $order->setCardType($paymentDetails['card_type']);

        $total = 0.0;

        foreach ($normalizedItems as $itemData) {
            $product = $this->entityManager->getRepository(Product::class)->find($itemData['product_id']);
            if (!$product instanceof Product) {
                throw new \InvalidArgumentException('One of the selected products no longer exists.');
            }

            $availableStock = $this->getAvailableStock($product);
            if ($availableStock < $itemData['quantity']) {
                throw new \RuntimeException(sprintf(
                    'Only %d stock(s) left for %s.',
                    $availableStock,
                    $product->getName() ?? 'the selected product'
                ));
            }

            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setQuantity($itemData['quantity']);
            $orderItem->setUnitPrice((float) ($product->getPrice() ?? 0));
            $order->addOrderItem($orderItem);

            $total += $orderItem->getLineTotal();
        }

        $order->setTotal($total);

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $fcmToken = $customer->getFcmToken();
        if ($fcmToken) {
            $this->fcm->send(
                $fcmToken,
                'Order Placed!',
                "Your order #{$order->getOrderNumber()} has been received.",
                ['order_id' => (string) $order->getId(), 'type' => 'order_created'],
            );
        }

        return $order;
    }

    private function isDuplicateOrder(Customer $customer, array $items, array $paymentDetails): bool
    {
        $latestOrder = $this->entityManager->getRepository(Order::class)
            ->findOneBy(['customer' => $customer], ['createdAt' => 'DESC']);

        if (!$latestOrder instanceof Order) {
            return false;
        }

        $latestCreatedAt = $latestOrder->getCreatedAt();
        if (!$latestCreatedAt instanceof \DateTimeImmutable) {
            return false;
        }

        $secondsSinceLast = (new \DateTimeImmutable())->getTimestamp() - $latestCreatedAt->getTimestamp();
        if ($secondsSinceLast > 5) {
            return false;
        }

        if ($latestOrder->getPaymentMethod() !== $paymentDetails['payment_method']) {
            return false;
        }

        if (($latestOrder->getGcashNumber() ?? '') !== ($paymentDetails['gcash_number'] ?? '')) {
            return false;
        }

        if (($latestOrder->getCardType() ?? '') !== ($paymentDetails['card_type'] ?? '')) {
            return false;
        }

        $latestItems = [];
        foreach ($latestOrder->getOrderItems() as $orderItem) {
            $productId = $orderItem->getProduct()?->getId();
            if (!$productId) {
                continue;
            }
            $latestItems[] = [
                'product_id' => (int) $productId,
                'quantity' => (int) ($orderItem->getQuantity() ?? 0),
            ];
        }

        $normalizedLatest = $this->normalizeItems($latestItems);
        if (count($normalizedLatest) !== count($items)) {
            return false;
        }

        $sorter = static fn(array $left, array $right) => ($left['product_id'] <=> $right['product_id']);
        usort($normalizedLatest, $sorter);
        usort($items, $sorter);

        foreach ($items as $index => $item) {
            if ($item['product_id'] !== $normalizedLatest[$index]['product_id']) {
                return false;
            }
            if ($item['quantity'] !== $normalizedLatest[$index]['quantity']) {
                return false;
            }
        }

        return true;
    }

    public function updateOrderStatus(Order $order, string $nextStatus): void
    {
        $allowedStatuses = ['pending', 'completed', 'cancelled'];
        if (!in_array($nextStatus, $allowedStatuses, true)) {
            throw new \InvalidArgumentException('Invalid order status.');
        }

        $currentStatus = $order->getStatus();
        if ($currentStatus === $nextStatus) {
            return;
        }

        if ($currentStatus !== 'pending') {
            throw new \RuntimeException('Only pending orders can be updated.');
        }

        if ($nextStatus === 'completed') {
            $this->deductStocks($order);
        }

        $order->setStatus($nextStatus);
        $this->entityManager->flush();

        $customer = $order->getCustomer();
        $fcmToken = $customer?->getFcmToken();
        if ($fcmToken) {
            $statusLabel = $nextStatus === 'completed' ? 'completed' : 'cancelled';
            $this->fcm->send(
                $fcmToken,
                'Order Update',
                "Your order #{$order->getOrderNumber()} has been {$statusLabel}.",
                ['order_id' => (string) $order->getId(), 'type' => 'order_status_changed'],
            );
        }
    }

    public function updatePendingOrder(
        Order $order,
        array $itemsData,
        string $paymentMethod = 'cash',
        ?string $gcashNumber = null,
        ?string $cardType = null,
    ): Order {
        if ($order->getStatus() !== 'pending') {
            throw new \RuntimeException('Only pending orders can be edited.');
        }

        $normalizedItems = $this->normalizeItems($itemsData);
        $paymentDetails = $this->normalizePaymentDetails($paymentMethod, $gcashNumber, $cardType);

        if ($normalizedItems === []) {
            throw new \InvalidArgumentException('Please select at least one product.');
        }

        foreach ($normalizedItems as $itemData) {
            $product = $this->entityManager->getRepository(Product::class)->find($itemData['product_id']);
            if (!$product instanceof Product) {
                throw new \InvalidArgumentException('One of the selected products no longer exists.');
            }

            $availableStock = $this->getAvailableStock($product);
            if ($availableStock < $itemData['quantity']) {
                throw new \RuntimeException(sprintf(
                    'Only %d stock(s) left for %s.',
                    $availableStock,
                    $product->getName() ?? 'the selected product'
                ));
            }
        }

        foreach (clone $order->getOrderItems() as $existingItem) {
            $order->removeOrderItem($existingItem);
        }

        $total = 0.0;

        foreach ($normalizedItems as $itemData) {
            $product = $this->entityManager->getRepository(Product::class)->find($itemData['product_id']);
            if (!$product instanceof Product) {
                continue;
            }

            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setQuantity($itemData['quantity']);
            $orderItem->setUnitPrice((float) ($product->getPrice() ?? 0));
            $order->addOrderItem($orderItem);

            $total += $orderItem->getLineTotal();
        }

        $order->setTotal($total);
        $order->setPaymentMethod($paymentDetails['payment_method']);
        $order->setGcashNumber($paymentDetails['gcash_number']);
        $order->setCardType($paymentDetails['card_type']);

        $this->entityManager->flush();

        return $order;
    }

    private function normalizeItems(array $itemsData): array
    {
        $normalizedItems = [];

        foreach ($itemsData as $itemData) {
            $productId = $itemData['product_id'] ?? $itemData['id'] ?? null;
            $quantity = (int) ($itemData['quantity'] ?? 0);

            if (!$productId || $quantity < 1) {
                continue;
            }

            $normalizedItems[] = [
                'product_id' => (int) $productId,
                'quantity' => $quantity,
            ];
        }

        return $normalizedItems;
    }

    private function normalizePaymentDetails(string $paymentMethod, ?string $gcashNumber, ?string $cardType): array
    {
        $normalized = strtolower(trim($paymentMethod));
        $allowedMethods = ['cash', 'gcash', 'card'];

        if (!in_array($normalized, $allowedMethods, true)) {
            throw new \InvalidArgumentException('Invalid payment method. Allowed: cash, gcash, card.');
        }

        $cleanGcash = $gcashNumber ? preg_replace('/\D+/', '', $gcashNumber) : null;
        $cleanCardType = $cardType ? strtolower(trim($cardType)) : null;

        if ($normalized === 'gcash') {
            if (!$cleanGcash || strlen($cleanGcash) < 10 || strlen($cleanGcash) > 13) {
                throw new \InvalidArgumentException('Valid GCash number is required for GCash payments.');
            }

            return [
                'payment_method' => $normalized,
                'gcash_number' => $cleanGcash,
                'card_type' => null,
            ];
        }

        if ($normalized === 'card') {
            $allowedCardTypes = ['visa', 'mastercard', 'amex', 'jcb'];
            if (!$cleanCardType || !in_array($cleanCardType, $allowedCardTypes, true)) {
                throw new \InvalidArgumentException('Card type is required for card payments.');
            }

            return [
                'payment_method' => $normalized,
                'gcash_number' => null,
                'card_type' => $cleanCardType,
            ];
        }

        return [
            'payment_method' => $normalized,
            'gcash_number' => null,
            'card_type' => null,
        ];
    }

    private function deductStocks(Order $order): void
    {
        foreach ($order->getOrderItems() as $orderItem) {
            $product = $orderItem->getProduct();
            if (!$product instanceof Product) {
                continue;
            }

            $availableStock = $this->getAvailableStock($product);
            if ($availableStock < (int) $orderItem->getQuantity()) {
                throw new \RuntimeException(sprintf(
                    'Insufficient stock to complete %s.',
                    $product->getName() ?? 'this order item'
                ));
            }
        }

        foreach ($order->getOrderItems() as $orderItem) {
            $product = $orderItem->getProduct();
            if (!$product instanceof Product) {
                continue;
            }

            $remaining = (int) $orderItem->getQuantity();
            foreach ($product->getStocks() as $stock) {
                if ($remaining <= 0) {
                    break;
                }

                $currentQuantity = (int) ($stock->getQuantity() ?? 0);
                if ($currentQuantity <= 0) {
                    continue;
                }

                $deduction = min($currentQuantity, $remaining);
                $stock->setQuantity($currentQuantity - $deduction);
                $stock->setLastUpdated(new \DateTime());
                $remaining -= $deduction;
            }
        }
    }

    private function getAvailableStock(Product $product): int
    {
        $availableStock = 0;

        foreach ($product->getStocks() as $stock) {
            $availableStock += (int) ($stock->getQuantity() ?? 0);
        }

        return $availableStock;
    }
}
