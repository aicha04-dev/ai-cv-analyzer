<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class GoogleAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private ClientRegistry $clientRegistry,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $client = $this->clientRegistry->getClient('google');

        $accessToken = $client->getAccessToken();

        $googleUser = $client->fetchUserFromToken($accessToken);

        $email = $googleUser->getEmail();

        if (!$email) {
            throw new AuthenticationException(
                'Google did not provide an email address.'
            );
        }

        return new SelfValidatingPassport(
            new UserBadge(
                $email,
                function (string $userIdentifier) use ($googleUser) {

                    $user = $this->userRepository->findOneBy([
                        'email' => $userIdentifier,
                    ]);

                    if ($user) {
                        return $user;
                    }

                    $user = new User();

                    $user->setEmail($userIdentifier);

                    $user->setFirstName(
                        $googleUser->toArray()['given_name']
                        ?? ''
                    );

                    $user->setLastName(
                        $googleUser->toArray()['family_name']
                        ?? ''
                    );

                    // Social login users don't need a password.
                    $user->setPassword(null);

                    $this->entityManager->persist($user);
                    $this->entityManager->flush();

                    return $user;
                }
            )
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
       return new \Symfony\Component\HttpFoundation\RedirectResponse(
     'https://ai-cv-analyzer-n131fx5l7-ai-cv-matcher.vercel.app/'
);
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): ?Response {
        return new Response(
            'Google authentication failed: ' .
            $exception->getMessage(),
            Response::HTTP_UNAUTHORIZED
        );
    }
}