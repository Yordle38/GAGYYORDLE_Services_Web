<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

use Symfony\Component\HttpFoundation\JsonResponse;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, JWTTokenManagerInterface $jwtManager): Response
    {
        // R�cup�rer les erreurs de l'authentification s'il y en a
        $error = $authenticationUtils->getLastAuthenticationError();

        // R�cup�rer le dernier nom d'utilisateur saisi par l'utilisateur
        $lastUsername = $authenticationUtils->getLastUsername();// Votre logique d'authentification ici, par exemple v�rifier les informations d'identification
        // ...

        // Si l'authentification est r�ussie, g�n�rer le token JWT
        if (!$error && $this->getUser()) {
            $token = $jwtManager->create($this->getUser());
            // Retourner le token JWT dans la r�ponse
            return $this->json(['token' => $token]);
        }

        // Rendre le formulaire de connexion avec les erreurs �ventuelles
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
