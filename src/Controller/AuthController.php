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
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        // Récupérer les données du corps de la requête
        $data = json_decode($request->getContent(), true);

        // Valider les données d'entrée
        if (!isset($data['nom'], $data['prenom'], $data['email'], $data['password'])) {
            return new JsonResponse(['error' => 'Tous les champs sont obligatoires'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Créer un nouvel utilisateur
        $client = new Client();
        $client->setNom($data['nom']);
        $client->setPrenom($data['prenom']);
        $client->setMail($data['email']);

        // Hasher le mot de passe
        $hashedPassword = $passwordHasher->hashPassword($client, $data['password']);
        $client->setMotDePasse($hashedPassword);

        // Persistez le client dans la base de données
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($client);
        $entityManager->flush();

        // Retourner une réponse JSON avec un message de succès
        return new JsonResponse(['message' => 'Inscription réussie', 'client' => $client->toArray()], JsonResponse::HTTP_CREATED);
    }
}
