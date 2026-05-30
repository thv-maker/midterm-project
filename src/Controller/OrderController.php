<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProductRepository;
use App\Service\ActivityLoggerService;
use App\Service\OrderMercurePublisher;
use App\Service\OrderWorkflowService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard/orders')]
#[IsGranted('ROLE_STAFF')]
class OrderController extends AbstractController
{
    #[Route('/', name: 'app_order_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        return $this->render('order/index.html.twig', [
            'orders' => $orderRepository->findBy([], ['createdAt' => 'DESC']),
            'pageLoadedAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ]);
    }

    #[Route('/feed', name: 'app_order_feed', methods: ['GET'])]
    public function feed(Request $request, OrderRepository $orderRepository): JsonResponse
    {
        $sinceParam = $request->query->get('since');
        if (!is_string($sinceParam) || $sinceParam === '') {
            return $this->json(['orders' => []]);
        }

        try {
            $since = new \DateTimeImmutable($sinceParam);
        } catch (\Exception) {
            return $this->json(['error' => 'Invalid since timestamp.'], 400);
        }

        $orders = $orderRepository->findFeedEventsAfter($since);

        return $this->json(['orders' => $orders]);
    }

    #[Route('/row/{id}', name: 'app_order_row', methods: ['GET'])]
    public function row(Order $order): Response
    {
        return $this->render('order/_row.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/new', name: 'app_order_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        CustomerRepository $customerRepository,
        ProductRepository $productRepository,
        OrderWorkflowService $orderWorkflowService,
    ): Response {
        if ($request->isMethod('POST')) {
            $customerId = $request->request->get('customer_id');
            $products = $request->request->all('products') ?? [];
            $paymentMethod = (string) $request->request->get('payment_method', 'cash');
            $gcashNumber = $request->request->get('gcash_number');
            $cardType = $request->request->get('card_type');

            if (empty($customerId) || empty($products)) {
                $this->addFlash('error', 'Please select a customer and at least one product.');
                return $this->redirectToRoute('app_order_new');
            }

            $customer = $customerRepository->find($customerId);
            if (!$customer) {
                $this->addFlash('error', 'Customer not found.');
                return $this->redirectToRoute('app_order_new');
            }

            try {
                $order = $orderWorkflowService->createOrder(
                    $customer,
                    $products,
                    $paymentMethod,
                    is_string($gcashNumber) ? $gcashNumber : null,
                    is_string($cardType) ? $cardType : null,
                );
            } catch (\InvalidArgumentException | \RuntimeException $exception) {
                $this->addFlash('error', $exception->getMessage());
                return $this->redirectToRoute('app_order_new');
            }

            $this->addFlash('success', 'Order created successfully!');
            return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
        }

        return $this->render('order/new.html.twig', [
            'customers' => $customerRepository->findAll(),
            'products' => $productRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order, OrderWorkflowService $orderWorkflowService): Response
    {
        // Only allow editing if order is pending
        if ($order->getStatus() !== 'pending') {
            $this->addFlash('error', 'Cannot edit completed or cancelled orders.');
            return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
        }

        if ($request->isMethod('POST')) {
            $status = $request->request->get('status');

            if (in_array($status, ['pending', 'completed', 'cancelled'], true)) {
                try {
                    $orderWorkflowService->updateOrderStatus($order, $status);
                } catch (\InvalidArgumentException | \RuntimeException $exception) {
                    $this->addFlash('error', $exception->getMessage());
                    return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
                }

                $successMessage = $status === 'completed'
                    ? 'Order completed and stock updated successfully!'
                    : 'Order updated successfully!';

                $this->addFlash('success', $successMessage);
                return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
            }
        }

        return $this->render('order/edit.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}', name: 'app_order_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Order $order,
        EntityManagerInterface $entityManager,
        OrderMercurePublisher $orderMercurePublisher,
        ActivityLoggerService $activityLogger,
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $order->getId(), $request->request->get('_token'))) {
            // Only allow deleting pending orders
            if ($order->getStatus() === 'pending') {
                $orderId = $order->getId();
                $orderNumber = (string) $order->getOrderNumber();
                $customerId = $order->getCustomer()?->getId();
                $entityManager->remove($order);
                $entityManager->flush();
                if ($orderId) {
                    $activityLogger->logOrderDeleted($orderId, $orderNumber);
                    $orderMercurePublisher->publishDeleted($orderId, $customerId);
                }
                $this->addFlash('success', 'Order deleted successfully!');
            } else {
                $this->addFlash('error', 'Cannot delete completed or cancelled orders.');
            }
        }

        return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/cancel', name: 'app_order_cancel', methods: ['POST'])]
    public function cancel(Request $request, Order $order, OrderWorkflowService $orderWorkflowService): Response
    {
        if (!$this->isCsrfTokenValid('cancel' . $order->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('app_order_index', [], Response::HTTP_SEE_OTHER);
        }

        if ($order->getStatus() !== 'pending') {
            $this->addFlash('error', 'Only pending orders can be cancelled.');
            return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
        }

        try {
            $orderWorkflowService->updateOrderStatus($order, 'cancelled');
        } catch (\InvalidArgumentException | \RuntimeException $exception) {
            $this->addFlash('error', $exception->getMessage());
            return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
        }

        $this->addFlash('success', 'Order cancelled successfully!');
        return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
    }
}
