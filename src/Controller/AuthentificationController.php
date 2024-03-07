<?php

namespace App\Controller;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthentificationController extends AbstractController
{
    //https://127.0.0.1:8000/login?email=test@gmail.com&password=test
//    #[Route('/login', name: 'app_login', methods: ['GET'])]
//    public function login(EntityManagerInterface $entityManager, Request $request, UserPasswordHasherInterface $passwordHasher, JWTTokenManagerInterface $jwtManager): JsonResponse {
//        $credentials = json_decode($request->getContent(), true);
//
//        if ($credentials === null) {
//            return new JsonResponse(['message' => 'Les données JSON sont invalides'], 400);
//        }
//
//        if (!isset($credentials['username']) || !isset($credentials['password'])) {
//            return new JsonResponse(['message' => 'Les champs email et password sont requis'], 400);
//        }
//
//        $user = $entityManager->getRepository(Client::class)->findOneBy(['email' => $credentials['username']]);
//
//        if (!$user || !$passwordHasher->isPasswordValid($user, $credentials['password'])) {
//            return new JsonResponse(['message' => 'Identifiants invalides'], 401);
//        }
//
//        $token = $jwtManager->create($user);
//
//        return new JsonResponse(['token' => $token]);
//    }

}