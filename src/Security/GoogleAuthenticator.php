<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private EntityManagerInterface $entityManager,
        private RouterInterface $router
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var \League\OAuth2\Client\Provider\GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);

                $email = $googleUser->getEmail();

                // Check if user already exists
                $existingUser = $this->entityManager->getRepository(User::class)
                    ->findOneBy(['email' => $email]);

                if ($existingUser) {
                    // Google-authenticated users are considered verified.
                    if (!$existingUser->isVerified()) {
                        $existingUser->setIsVerified(true);
                        $this->entityManager->flush();
                    }
                    return $existingUser;
                }

                // Create new user if not found
                $user = new User();
                $user->setEmail($email);
                $user->setIsVerified(true);
                $user->setIsActive(true);
                $domain = strtolower(substr(strrchr($email, '@') ?: '', 1));
                $user->setRoles(['ROLE_STAFF']);
                $user->setPassword('');

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
{
    return new RedirectResponse($this->router->generate('app_dashboard_index'));
}

public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
{
    return new RedirectResponse($this->router->generate('app_login'));
}

}