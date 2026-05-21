<?php
namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

#[Route('/api', name: 'api_')]
class ApiAuthController extends AbstractController
{
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer,
    ) {
    }

    private function success(string $message, array $data = [], int $status = 200): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'error' => null,
        ], $status);
    }

    private function error(string $message, int $status = 400): JsonResponse
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'error' => [
                'code' => $status,
                'detail' => $message,
            ],
        ], $status);
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->success('API is running.', [
            'endpoints' => [
                '/api/login' => 'POST',
                '/api/register' => 'POST',
                '/api/products' => 'GET',
            ],
        ]);
    }

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return $this->error('Email and password are required.', 400);
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return $this->error('Invalid credentials.', 401);
        }

        if (!$user->isVerified()) {
            return $this->error('Please verify your email before logging in.', 403);
        }

        $token = $jwtManager->create($user);

        return $this->success('Login successful.', [
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'isVerified' => $user->isVerified(),
            ],
        ]);
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return $this->error('Email and password are required.', 400);
        }

        $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            return $this->error('Email already exists.', 400);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(false);
        $user->setIsActive(true);

        $em->persist($user);
        $em->flush();

        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            'app_verify_email',
            $user->getId(),
            $user->getEmail(),
            ['id' => $user->getId()]
        );

        $emailMessage = (new TemplatedEmail())
            ->from(new Address('carpediemcafe6@gmail.com', 'Carpe Diem Coffee'))
            ->to($user->getEmail())
            ->subject('Please Verify Your Email')
            ->htmlTemplate('registration/confirmation_email.html.twig')
            ->context([
                'signedUrl' => $signatureComponents->getSignedUrl(),
                'expiresAt' => $signatureComponents->getExpiresAt(),
            ]);

        $this->mailer->send($emailMessage);

        return $this->success('Registration successful. Please verify your email.', [
            'userId' => $user->getId(),
            'email' => $user->getEmail(),
            'isVerified' => $user->isVerified(),
        ], 201);
    }

    #[Route('/products', name: 'api_products', methods: ['GET'])]
    public function products(EntityManagerInterface $em): JsonResponse
    {
        $products = $em->getRepository(Product::class)->findBy([], ['id' => 'DESC']);

        $payload = array_map(static function (Product $product): array {
            return [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'category' => $product->getCategory(),
                'price' => $product->getPrice(),
                'calories' => $product->getCalories(),
                'sugarGrams' => $product->getSugarGrams(),
                'caffeineMg' => $product->getCaffeineMg(),
            ];
        }, $products);

        return $this->success('Products retrieved successfully.', [
            'items' => $payload,
            'count' => count($payload),
        ]);
    }
}