<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GoogleAuthController
{
    #[Route('/connect/google', name: 'connect_google_start')]
    public function connect(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect([
                'email',
                'profile',
            ]);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function check(): Response
    {
        // GoogleAuthenticator handles this route.
        throw new \LogicException(
            'This route should be handled by GoogleAuthenticator.'
        );
    }
}