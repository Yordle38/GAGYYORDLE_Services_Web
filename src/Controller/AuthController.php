<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\Client;

class AuthController extends AbstractController
{
    // #[Route('/register', name: 'register', methods: ['POST'])]
    // public function register(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    // {
        // // R�cup�rer les donn�es du corps de la requ�te
        // $data = json_decode($request->getContent(), true);

        // // Valider les donn�es d'entr�e
        // if (!isset($data['nom'], $data['prenom'], $data['email'], $data['password'])) {
        //     return new JsonResponse(['error' => 'Tous les champs sont obligatoires'], JsonResponse::HTTP_BAD_REQUEST);
        // }

        // // Cr�er un nouvel utilisateur
        // $client = new Client();
        // $client->setNom($data['nom']);
        // $client->setPrenom($data['prenom']);
        // $client->setMail($data['email']);

        // // Hasher le mot de passe
        // $hashedPassword = $passwordHasher->hashPassword($client, $data['password']);
        // $client->setMotDePasse($hashedPassword);

        // // Persistez le client dans la base de donn�es
        // $entityManager = $this->getDoctrine()->getManager();
        // $entityManager->persist($client);
        // $entityManager->flush();

        // // Retourner une r�ponse JSON avec un message de succ�s
        // return new JsonResponse(['message' => 'Inscription r�ussie', 'client' => $client->toArray()], JsonResponse::HTTP_CREATED);
    // }
}
