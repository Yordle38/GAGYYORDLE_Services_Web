<?php

namespace App\Controller;

use App\Entity\Client;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;


use Symfony\Component\HttpFoundation\JsonResponse;

class SecurityController extends AbstractController
{



    #[Route('/login', name: 'token', methods: ['POST'])]
    public function login(Request $request, UserPasswordHasherInterface $passwordHasher, JWTTokenManagerInterface $jwtManager, EntityManagerInterface $entityManager): JsonResponse
    {
        // Récupérer les données JSON envoyées dans la requête
        $data = json_decode($request->getContent(), true);

        // Vérifier si les données requises sont présentes dans la requête
        if (!isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse(['error' => 'Email et mot de passe requis'], Response::HTTP_BAD_REQUEST);
        }

        // Récupérer le client depuis la base de données en fonction de l'email fourni
        $client = $entityManager->getRepository(Client::class)->findOneBy(['email' => $data['email']]);

        // Vérifier si le client existe et si le mot de passe est correct
        if (!$client || !$passwordHasher->isPasswordValid($client, $data['password'])) {
            return new JsonResponse(['error' => 'Identifiants invalides'], Response::HTTP_UNAUTHORIZED);
        }

        // Si l'authentification est réussie, générer le token JWT
        $token = $jwtManager->create($client);

        // Retourner le token JWT dans la réponse
        return new JsonResponse(['token' => $token]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
