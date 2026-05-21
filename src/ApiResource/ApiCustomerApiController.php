<?php

namespace App\Controller\Api;

use App\Entity\Customer;
use App\Entity\Order;
use App\Entity\User;
use App\Repository\CustomerRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/customer')]
class CustomerApiController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private JWTTokenManagerInterface $jwtManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    // ── REGISTER ──────────────────────────────────────────────
    #[Route('/register', name: 'api_customer_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $required = ['name', 'email', 'password', 'phone', 'address'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->json(['error' => "Field '$field' is required."], 400);
            }
        }

        // Check if email already exists
        $existing = $this->em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existing) {
            return $this->json(['error' => 'Email already registered.'], 409);
        }

        // Create User
        $user = new User();
        $user->setEmail($data['email']);
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $data['password']));
        $user->setIsActive(true);
        $user->setIsVerified(false);

        // Create Customer
        $customer = new Customer();
        $customer->setName($data['name']);
        $customer->setEmail($data['email']);
        $customer->setPhone($data['phone']);
        $customer->setAddress($data['address']);

        $this->em->persist($user);
        $this->em->persist($customer);
        $this->em->flush();

        $token = $this->jwtManager->createFromPayload($user, [
            'email'       => $user->getEmail(),
            'roles'       => $user->getRoles(),
            'customer_id' => $customer->getId(),
        ]);

        return $this->json([
            'message'     => 'Registration successful!',
            'token'       => $token,
            'customer_id' => $customer->getId(),
            'name'        => $customer->getName(),
            'email'       => $customer->getEmail(),
        ], 201);
    }

    // ── LOGIN ─────────────────────────────────────────────────
    #[Route('/login', name: 'api_customer_login', methods: ['POST'])]
    public function login(Request $request, CustomerRepository $customerRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['email']) || empty($data['password'])) {
            return $this->json(['error' => 'Email and password are required.'], 400);
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $data['email']]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return $this->json(['error' => 'Invalid email or password.'], 401);
        }

        if (!$user->isActive()) {
            return $this->json(['error' => 'Account is inactive.'], 403);
        }

        // Update last login
        $user->setLastLogin(new \DateTime());
        $this->em->flush();

        $customer = $customerRepository->findOneBy(['email' => $user->getEmail()]);

        $token = $this->jwtManager->createFromPayload($user, [
            'email'       => $user->getEmail(),
            'roles'       => $user->getRoles(),
            'customer_id' => $customer?->getId(),
        ]);

        return $this->json([
            'message'     => 'Login successful!',
            'token'       => $token,
            'customer_id' => $customer?->getId(),
            'name'        => $customer?->getName(),
            'email'       => $user->getEmail(),
        ]);
    }

    // ── GET PRODUCTS ──────────────────────────────────────────
    #[Route('/products', name: 'api_customer_products', methods: ['GET'])]
    public function products(ProductRepository $productRepository): JsonResponse
    {
        $products = $productRepository->findAll();

        $data = array_map(function ($product) {
            $totalStock = 0;
            foreach ($product->getStocks() as $stock) {
                $totalStock += $stock->getQuantity();
            }

            return [
                'id'          => $product->getId(),
                'name'        => $product->getName(),
                'category'    => $product->getCategory(),
                'price'       => $product->getPrice(),
                'calories'    => $product->getCalories(),
                'sugar_grams' => $product->getSugarGrams(),
                'caffeine_mg' => $product->getCaffeineMg(),
                'stock'       => $totalStock,
                'available'   => $totalStock > 0,
            ];
        }, $products);

        return $this->json(['products' => $data]);
    }

    // ── PLACE ORDER ───────────────────────────────────────────
    #[Route('/orders', name: 'api_customer_place_order', methods: ['POST'])]
    public function placeOrder(Request $request, CustomerRepository $customerRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['customer_id']) || empty($data['total'])) {
            return $this->json(['error' => 'customer_id and total are required.'], 400);
        }

        $customer = $customerRepository->find($data['customer_id']);
        if (!$customer) {
            return $this->json(['error' => 'Customer not found.'], 404);
        }

        $order = new Order();
        $order->setOrderNumber('ORD-' . strtoupper(uniqid()));
        $order->setTotal($data['total']);
        $order->setStatus('pending');
        $order->setCreatedAt(new \DateTimeImmutable());
        $order->setCustomer($customer);

        $this->em->persist($order);
        $this->em->flush();

        return $this->json([
            'message'      => 'Order placed successfully!',
            'order_id'     => $order->getId(),
            'order_number' => $order->getOrderNumber(),
            'total'        => $order->getTotal(),
            'status'       => $order->getStatus(),
            'created_at'   => $order->getCreatedAt()->format('Y-m-d H:i:s'),
        ], 201);
    }

    // ── GET ORDER HISTORY ─────────────────────────────────────
    #[Route('/orders/{customerId}', name: 'api_customer_orders', methods: ['GET'])]
    public function orders(int $customerId, CustomerRepository $customerRepository): JsonResponse
    {
        $customer = $customerRepository->find($customerId);
        if (!$customer) {
            return $this->json(['error' => 'Customer not found.'], 404);
        }

        $orders = array_map(function ($order) {
            return [
                'id'           => $order->getId(),
                'order_number' => $order->getOrderNumber(),
                'total'        => $order->getTotal(),
                'status'       => $order->getStatus(),
                'created_at'   => $order->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $customer->getOrders()->toArray());

        return $this->json([
            'customer' => $customer->getName(),
            'orders'   => $orders,
            'total_orders' => count($orders),
        ]);
    }

    // ── GET PROFILE ───────────────────────────────────────────
    #[Route('/profile/{customerId}', name: 'api_customer_profile', methods: ['GET'])]
    public function profile(int $customerId, CustomerRepository $customerRepository): JsonResponse
    {
        $customer = $customerRepository->find($customerId);
        if (!$customer) {
            return $this->json(['error' => 'Customer not found.'], 404);
        }

        return $this->json([
            'id'      => $customer->getId(),
            'name'    => $customer->getName(),
            'email'   => $customer->getEmail(),
            'phone'   => $customer->getPhone(),
            'address' => $customer->getAddress(),
            'total_orders' => $customer->getOrders()->count(),
        ]);
    }
}