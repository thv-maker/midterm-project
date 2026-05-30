<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class ActivityLoggerService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private RequestStack $requestStack,
        private ActivityLogMercurePublisher $activityLogPublisher,
    ) {}

    public function logLogin(User $user, string $source = 'web'): void
    {
        $this->logAs(
            $user,
            'LOGIN',
            sprintf('User login (%s)', $source)
        );
    }

    public function logLogout(User $user, string $source = 'web'): void
    {
        $this->logAs(
            $user,
            'LOGOUT',
            sprintf('User logout (%s)', $source)
        );
    }

    public function logRegister(User $user): void
    {
        $this->logAs(
            $user,
            'REGISTER',
            sprintf('New account registered: %s', $user->getEmail())
        );
    }

    public function logUserCreate(User $createdUser): void
    {
        $this->logAs(
            $this->getCurrentUser(),
            'CREATE',
            sprintf('User: %s (ID: %d)', $createdUser->getEmail(), $createdUser->getId())
        );
    }

    public function logUserDelete(int $deletedUserId, string $deletedUserEmail): void
    {
        $this->logAs(
            $this->getCurrentUser(),
            'DELETE',
            sprintf('User: %s (ID: %d)', $deletedUserEmail, $deletedUserId)
        );
    }

    public function logUserUpdate(User $updatedUser): void
    {
        $this->logAs(
            $this->getCurrentUser(),
            'UPDATE',
            sprintf('User: %s (ID: %d)', $updatedUser->getEmail(), $updatedUser->getId())
        );
    }

    public function logCreate(string $entityType, int $entityId, string $entityName): void
    {
        $this->logAs(
            $this->getCurrentUser(),
            'CREATE',
            sprintf('%s: %s (ID: %d)', $entityType, $entityName, $entityId)
        );
    }

    public function logUpdate(string $entityType, int $entityId, string $entityName): void
    {
        $this->logAs(
            $this->getCurrentUser(),
            'UPDATE',
            sprintf('%s: %s (ID: %d)', $entityType, $entityName, $entityId)
        );
    }

    public function logDelete(string $entityType, int $entityId, string $entityName): void
    {
        $this->logAs(
            $this->getCurrentUser(),
            'DELETE',
            sprintf('%s: %s (ID: %d)', $entityType, $entityName, $entityId)
        );
    }

    public function logOrderPlaced(Order $order, ?User $actor = null): void
    {
        $customer = $order->getCustomer();
        $customerName = $customer?->getName() ?? 'Unknown customer';

        $this->logAs(
            $actor ?? $this->resolveActorForCustomer($customer) ?? $this->getCurrentUser(),
            'ORDER',
            sprintf(
                'Order %s (ID: %d) placed by %s — Total: ₱%.2f',
                $order->getOrderNumber(),
                $order->getId(),
                $customerName,
                (float) $order->getTotal()
            )
        );
    }

    public function logOrderUpdated(Order $order, ?User $actor = null): void
    {
        $this->logAs(
            $actor ?? $this->resolveActorForCustomer($order->getCustomer()) ?? $this->getCurrentUser(),
            'UPDATE',
            sprintf(
                'Order %s (ID: %d) updated — Total: ₱%.2f',
                $order->getOrderNumber(),
                $order->getId(),
                (float) $order->getTotal()
            )
        );
    }

    public function logOrderStatusChange(
        Order $order,
        string $fromStatus,
        string $toStatus,
        ?User $actor = null,
    ): void {
        $this->logAs(
            $actor ?? $this->resolveActorForCustomer($order->getCustomer()) ?? $this->getCurrentUser(),
            'UPDATE',
            sprintf(
                'Order %s (ID: %d): status changed from %s to %s',
                $order->getOrderNumber(),
                $order->getId(),
                $fromStatus,
                $toStatus
            )
        );
    }

    public function logOrderDeleted(int $orderId, string $orderNumber, ?User $actor = null): void
    {
        $this->logAs(
            $actor ?? $this->getCurrentUser(),
            'DELETE',
            sprintf('Order %s (ID: %d) deleted', $orderNumber, $orderId)
        );
    }

    private function logAs(?User $user, string $action, string $targetData): void
    {
        $user ??= $this->getCurrentUser();

        if (!$user instanceof User) {
            return;
        }

        $activityLog = new ActivityLog();
        $activityLog->setUserId($user->getId());
        $activityLog->setUsername((string) $user->getEmail());
        $activityLog->setRole($this->getUserPrimaryRole($user));
        $activityLog->setAction($action);
        $activityLog->setTargetData($targetData);
        $activityLog->setDateTime(new \DateTime());
        $activityLog->setUser($user);

        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $activityLog->setIpAddress($request->getClientIp());
            $activityLog->setUserAgent($request->headers->get('User-Agent'));
        }

        $this->entityManager->persist($activityLog);
        $this->entityManager->flush();

        $this->activityLogPublisher->publishCreated($activityLog);
    }

    private function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $user : null;
    }

    private function resolveActorForCustomer(?Customer $customer): ?User
    {
        if (!$customer instanceof Customer) {
            return null;
        }

        $email = $customer->getEmail();
        if (!$email) {
            return null;
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        return $user instanceof User ? $user : null;
    }

    private function getUserPrimaryRole(User $user): string
    {
        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles, true)) {
            return 'ROLE_ADMIN';
        }

        if (in_array('ROLE_STAFF', $roles, true)) {
            return 'ROLE_STAFF';
        }

        return 'ROLE_USER';
    }
}
