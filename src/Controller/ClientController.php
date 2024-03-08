<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Doctrine\ORM\EntityManagerInterface;


class ClientController extends AbstractController
{
    #[Route('/client', name: 'app_client')]
    public function index(): Response
    {
        return $this->render('client/index.html.twig', [
            'controller_name' => 'ClientController',
        ]);
    }

    #[Route('/client/login', name: 'client_login', methods: ['POST'])]
    public function login(Request $request, UserProviderInterface $userProvider, UserPasswordEncoderInterface $passwordEncoder, JWTTokenManagerInterface $jwtManager, EntityManagerInterface $entityManager): Response
    {
        // R�cup�rer les donn�es du corps de la requ�te
        $data = json_decode($request->getContent(), true);

        // V�rifier si les donn�es obligatoires sont pr�sentes
        if (!isset($data['username'], $data['password'])) {
            return $this->json(['error' => 'Le nom d\'utilisateur et le mot de passe sont obligatoires'], Response::HTTP_BAD_REQUEST);
        }

        // R�cup�rer l'utilisateur depuis le fournisseur d'utilisateurs (par exemple, la base de donn�es)
        $user = $userProvider->loadUserByUsername($data['username']);

        // V�rifier si l'utilisateur existe et si le mot de passe est correct
        if (!$user || !$passwordEncoder->isPasswordValid($user, $data['password'])) {
            return $this->json(['error' => 'Nom d\'utilisateur ou mot de passe incorrect'], Response::HTTP_UNAUTHORIZED);
        }

        // G�n�rer le token JWT
        $token = $jwtManager->create($user);

        // Utiliser l'EntityManager pour effectuer des op�rations sur la base de donn�es
        // Par exemple, $entityManager->persist($user); $entityManager->flush();

        // Retourner le token JWT dans la r�ponse
        return $this->json(['token' => $token]);
    }

}