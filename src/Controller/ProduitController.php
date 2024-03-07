<?php

namespace App\Controller;

use App\Entity\Magasin;
use App\Entity\Produit;
use App\Entity\Stock;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProduitController extends AbstractController
{
    #[Route('/produit', name: 'app_produit')]
    public function index(): Response
    {
        return $this->render('produit/index.html.twig', [
            'controller_name' => 'ProduitController',
        ]);
    }

    #[Route ('/produit/supprimer/{idMagasin}/{idProduit}', name: 'ajouter_magasin', methods: ['DELETE'])]
    public function delete($idMagasin, $idProduit, Request $request, EntityManagerInterface $entityManager, JWTTokenManagerInterface $jwtManager): Response
    {
        // Récupérer le token d'authentification depuis l'en-tête Authorization
        $token = $request->headers->get('Authorization');

        // Vérifie si le token JWT est vide ou s'il ne commence pas par "Bearer "
        if (!$token || strpos($token, 'Bearer ') !== 0) {
            return new Response('non autorisé', Response::HTTP_UNAUTHORIZED);
        }
        $tokenParts = explode(".", $token);
        $tokenPayload = base64_decode($tokenParts[1]);

        // Vérifie si le décodage a réussi
        if (!$tokenPayload) {
            return new Response('token non valide', Response::HTTP_UNAUTHORIZED);
        }

        $jwtPayload = json_decode($tokenPayload);

        // Vérifie si la charge utile du JWT contient l'identifiant de l'utilisateur
        if (!isset($jwtPayload->username)) {
            return new Response('Utilisateur non connecté', Response::HTTP_UNAUTHORIZED);
        }

        $tokenParts = explode(".", $token);

        $tokenPayload = base64_decode($tokenParts[1]);
        $jwtPayload = json_decode($tokenPayload);

        $role=$jwtPayload->roles[0];

        if($role != "ROLE_ADMIN"){
            return new Response('non autorisé', Response::HTTP_UNAUTHORIZED);
        }


// Recherche du magasin dans la base de données
        $magasin = $entityManager->getRepository(Magasin::class)->find($idMagasin);

        // Vérifie si le magasin existe
        if (!$magasin) {
            return new Response('Magasin non trouvé', Response::HTTP_NOT_FOUND);
        }

        // Recherche du produit dans le magasin
        $stock = $entityManager->getRepository(Stock::class)->findOneBy(['magasin' => $magasin, 'produit' => $idProduit]);

        // Vérifie si le stock existe
        if (!$stock) {
            return new Response('Produit non trouvé dans ce magasin', Response::HTTP_NOT_FOUND);
        }

        // Suppression du stock du produit dans le magasin
        $entityManager->remove($stock);
        $entityManager->flush();

        // Retourne une réponse indiquant que la suppression a réussi
        return new Response('Produit supprimé du magasin avec succès', Response::HTTP_OK);
    }

    #[Route('/produit/ajouter/{idMagasin}/{idProduit}/{quantite}', name: 'ajouter_produit_magasin', methods: ['POST'])]
    public function ajouter($idMagasin, $idProduit, $quantite, Request $request, EntityManagerInterface $entityManager, JWTTokenManagerInterface $jwtManager): Response
    {
        // Récupérer le token d'authentification depuis l'en-tête Authorization
        $token = $request->headers->get('Authorization');

        // Vérifie si le token JWT est vide ou s'il ne commence pas par "Bearer "
        if (!$token || strpos($token, 'Bearer ') !== 0) {
            return new Response('non autorisé', Response::HTTP_UNAUTHORIZED);
        }
        $tokenParts = explode(".", $token);
        $tokenPayload = base64_decode($tokenParts[1]);

        // Vérifie si le décodage a réussi
        if (!$tokenPayload) {
            return new Response('token non valide', Response::HTTP_UNAUTHORIZED);
        }

        $jwtPayload = json_decode($tokenPayload);

        // Vérifie si la charge utile du JWT contient l'identifiant de l'utilisateur
        if (!isset($jwtPayload->username)) {
            return new Response('Utilisateur non connecté', Response::HTTP_UNAUTHORIZED);
        }

        $tokenParts = explode(".", $token);

        $tokenPayload = base64_decode($tokenParts[1]);
        $jwtPayload = json_decode($tokenPayload);

        $role = $jwtPayload->roles[0];

        if ($role != "ROLE_ADMIN") {
            return new Response('non autorisé', Response::HTTP_UNAUTHORIZED);
        }

        // Recherche du magasin dans la base de données
        $magasin = $entityManager->getRepository(Magasin::class)->find($idMagasin);

        // Vérifie si le magasin existe
        if (!$magasin) {
            return new Response('Magasin non trouvé', Response::HTTP_NOT_FOUND);
        }

        // Recherche du produit dans la base de données
        $produit = $entityManager->getRepository(Produit::class)->find($idProduit);

        // Vérifie si le produit existe
        if (!$produit) {
            return new Response('Produit non trouvé', Response::HTTP_NOT_FOUND);
        }

        // Création d'une nouvelle instance de Stock
        $stock = new Stock();
        $stock->setMagasin($magasin);
        $stock->setProduit($produit);
        $stock->setQuantite($quantite); // Définit la quantité du produit dans le stock

        // Ajout du stock à l'EntityManager
        $entityManager->persist($stock);
        $entityManager->flush();

        // Retourne une réponse indiquant que l'ajout a réussi
        return new Response('Produit ajouté au magasin avec succès', Response::HTTP_CREATED);
    }

    #[Route('/produits/add', name: 'ajouter_produit', methods: ['POST'])]
    public function ajouterProduit(Request $request, EntityManagerInterface $entityManager, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        $token = $request->headers->get('Authorization');

        // Vérifie si le token JWT est vide ou s'il ne commence pas par "Bearer "
        if (!$token || strpos($token, 'Bearer ') !== 0) {
            return new Response('non autorisé', Response::HTTP_UNAUTHORIZED);
        }
        $tokenParts = explode(".", $token);
        $tokenPayload = base64_decode($tokenParts[1]);

        // Vérifie si le décodage a réussi
        if (!$tokenPayload) {
            return new Response('token non valide', Response::HTTP_UNAUTHORIZED);
        }

        $jwtPayload = json_decode($tokenPayload);

        // Vérifie si la charge utile du JWT contient l'identifiant de l'utilisateur
        if (!isset($jwtPayload->username)) {
            return new Response('Utilisateur non connecté', Response::HTTP_UNAUTHORIZED);
        }

        $tokenParts = explode(".", $token);

        $tokenPayload = base64_decode($tokenParts[1]);
        $jwtPayload = json_decode($tokenPayload);

        $role = $jwtPayload->roles[0];

        if ($role != "ROLE_ADMIN") {
            return new Response('non autorisé', Response::HTTP_UNAUTHORIZED);
        }

        // Récupérer les données du corps de la requête
        $data = json_decode($request->getContent(), true);

        // Vérifier si les données obligatoires sont présentes
        if (!isset($data['nom'], $data['prix'])) {
            return new JsonResponse(['error' => 'Le nom et le prix du produit sont obligatoires'], Response::HTTP_BAD_REQUEST);
        }

        // Créer une nouvelle instance de Produit
        $produit = new Produit();
        $produit->setNom($data['nom']);
        $produit->setPrix($data['prix']);

        // Persister le produit dans la base de données
        try {
            $entityManager->persist($produit);
            $entityManager->flush();

            return new JsonResponse(['message' => 'Produit ajouté avec succès', 'produit' => $produit->toArray()], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Une erreur s\'est produite lors de l\'ajout du produit: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/produit/supprimer/{idProduit}', name: 'supprimer_produit', methods: ['DELETE'])]
    public function supprimerProduit(int $idProduit,Request $request, EntityManagerInterface $entityManager, JWTTokenManagerInterface $jwtManager): Response
    {
        // Récupérer le token d'authentification depuis l'en-tête Authorization
        $token = $request->headers->get('Authorization');

        // Vérifie si le token JWT est vide ou s'il ne commence pas par "Bearer "
        if (!$token || strpos($token, 'Bearer ') !== 0) {
            return new Response('non autorisé', Response::HTTP_UNAUTHORIZED);
        }
        $tokenParts = explode(".", $token);
        $tokenPayload = base64_decode($tokenParts[1]);

        // Vérifie si le décodage a réussi
        if (!$tokenPayload) {
            return new Response('token non valide', Response::HTTP_UNAUTHORIZED);
        }

        $jwtPayload = json_decode($tokenPayload);

        // Vérifie si la charge utile du JWT contient l'identifiant de l'utilisateur
        if (!isset($jwtPayload->username)) {
            return new Response('Utilisateur non connecté', Response::HTTP_UNAUTHORIZED);
        }

        $tokenParts = explode(".", $token);

        $tokenPayload = base64_decode($tokenParts[1]);
        $jwtPayload = json_decode($tokenPayload);

        $role = $jwtPayload->roles[0];

        if ($role != "ROLE_ADMIN") {
            return new Response('non autorisé', Response::HTTP_UNAUTHORIZED);
        }

        // Rechercher le produit dans la base de données
        $produit = $entityManager->getRepository(Produit::class)->find($idProduit);

        // Vérifier si le produit existe
        if (!$produit) {
            return new JsonResponse(['error' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }

        try {
            // Supprimer le produit de la base de données
            $entityManager->remove($produit);
            $entityManager->flush();

            return new JsonResponse(['message' => 'Produit supprimé avec succès'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Une erreur s\'est produite lors de la suppression du produit: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
