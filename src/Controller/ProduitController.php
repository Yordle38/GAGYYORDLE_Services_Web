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

        if (!$idMagasin || !$idProduit) {
            return new Response("Le magasin ou le produit n'ont pas été saisis", Response::HTTP_NOT_FOUND);
        }


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


        if (!is_int($idMagasin) || !is_int($idProduit) || !is_int($quantite)) {
            return new Response("Le magasin, le produit ou la quantité n'ont pas été saisis correctement", Response::HTTP_NOT_FOUND);
        }

        if (!$idMagasin || !$idProduit || !$quantite ){
            return new Response("Le magasin, le produit ou la quantité n'ont pas été saisis", Response::HTTP_NOT_FOUND);
        }

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

        $nom = $data['nom'];
        $prix = $data['prix'];

        // Vérifier si le nom n'est pas un nombre
        if (is_numeric($nom)) {
            return new JsonResponse(['error' => 'Le nom du produit ne peut pas être un nombre'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si le prix est un nombre réel
        if (!is_numeric($prix) || !is_float($prix + 0)) {
            return new JsonResponse(['error' => 'Le prix du produit doit être un nombre réel'], Response::HTTP_BAD_REQUEST);
        }

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

    #[Route('/produit/{idMagasin}/{idProduit}', name: 'modifierQuantiteProduit', methods: ['PUT'])]
    public function modifierQuantiteProduit(int $idMagasin, int $idProduit, Request $request, EntityManagerInterface $entityManager, JWTTokenManagerInterface $jwtManager): Response
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

        // Récupérer le magasin
        $magasin = $entityManager->getRepository(Magasin::class)->find($idMagasin);
        if (!$magasin) {
            return new JsonResponse(['error' => 'Magasin non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Récupérer le produit
        $produit = $entityManager->getRepository(Produit::class)->find($idProduit);
        if (!$produit) {
            return new JsonResponse(['error' => 'Produit non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Récupérer le stock du produit dans le magasin
        $stock = $entityManager->getRepository(Stock::class)->findOneBy(['produit' => $produit, 'magasin' => $magasin]);
        if (!$stock) {
            return new JsonResponse(['error' => 'Stock non trouvé pour ce produit dans ce magasin'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Récupérer la quantité envoyée dans le corps de la requête
        $data = json_decode($request->getContent(), true);
        $nouvelleQuantite = $data['quantite'] ?? null;

        // Vérifier si la nouvelle quantité est valide
        if (!isset($nouvelleQuantite) || !is_numeric($nouvelleQuantite) || $nouvelleQuantite < 0) {
            return new JsonResponse(['error' => 'Quantité invalide'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Mettre à jour la quantité du stock du produit dans le magasin
        $stock->setQuantite($nouvelleQuantite);
        $entityManager->flush();

        // Retourner une réponse avec la nouvelle quantité du produit dans le magasin
        return new JsonResponse(['message' => 'Quantité du produit mise à jour avec succès', 'nouvelle_quantite' => $nouvelleQuantite], JsonResponse::HTTP_OK);
    }

    #[Route('/produit/actualisation/{idMagasin}/{idProduit}', name: 'modifier_quantite_produit', methods: ['PUT'])]
    public function AjoutSoustractionQuantite($idMagasin, $idProduit, Request $request, EntityManagerInterface $entityManager): Response
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

        // Recherche du stock du produit dans le magasin dans la base de données
        $stock = $entityManager->getRepository(Stock::class)->findOneBy(['magasin' => $idMagasin, 'produit' => $idProduit]);

        // Vérifie si le stock existe
        if (!$stock) {
            return new JsonResponse(['error' => 'Stock non trouvé pour ce produit dans ce magasin'], Response::HTTP_NOT_FOUND);
        }

        // Récupérer les données du corps de la requête
        $data = json_decode($request->getContent(), true);

        // Vérifier si la quantité est présente dans les données de la requête
        if (!isset($data['quantite'])) {
            return new JsonResponse(['error' => 'La quantité est requise'], Response::HTTP_BAD_REQUEST);
        }

        // Mettre à jour la quantité du stock en ajoutant ou en soustrayant la quantité envoyée dans la requête
        $nouvelleQuantite = $stock->getQuantite() + $data['quantite'];

        if ($nouvelleQuantite < 0) {
            return new JsonResponse(['error' => 'Stock insuffisant'], Response::HTTP_BAD_REQUEST);
        }

        $stock->setQuantite($nouvelleQuantite);

        try {
            // Enregistrer les modifications dans la base de données
            $entityManager->flush();
            return new JsonResponse(['message' => 'Quantité du produit mise à jour avec succès'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Une erreur s\'est produite lors de la mise à jour de la quantité du produit'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/produit/name/{id}', name: 'modifier_nom_produit', methods: ['PUT'])]
    public function modifierNomProduit(int $id, Request $request, EntityManagerInterface $entityManager): Response
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

        // Récupérer les données JSON envoyées dans la requête
        $data = json_decode($request->getContent(), true);

        // Vérifier si la clé "nom" existe dans les données envoyées
        if (!isset($data['name'])) {
            return new JsonResponse(['error' => 'Le nouveau nom du produit est requis'], Response::HTTP_BAD_REQUEST);
        }

        // Rechercher le produit dans la base de données
        $produit = $entityManager->getRepository(Produit::class)->find($id);

        // Vérifier si le produit existe
        if (!$produit) {
            return new JsonResponse(['error' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Modifier le nom du produit
        $nouveauNom = $data['name'];
        $produit->setNom($nouveauNom);

        // Enregistrer les modifications dans la base de données
        $entityManager->flush();

        // Retourner une réponse indiquant que le nom du produit a été modifié avec succès
        return new JsonResponse(['message' => 'Nom du produit modifié avec succès'], Response::HTTP_OK);
    }

    #[Route('/produit/prix/{id}', name: 'modifier_prix_produit', methods: ['PUT'])]
    public function modifierPrixProduit(int $id, Request $request, EntityManagerInterface $entityManager): Response
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

        // Récupérer les données JSON envoyées dans la requête
        $data = json_decode($request->getContent(), true);

        // Vérifier si la clé "prix" existe dans les données envoyées
        if (!isset($data['price'])) {
            return new JsonResponse(['error' => 'Le nouveau prix du produit est requis'], Response::HTTP_BAD_REQUEST);
        }

        // Rechercher le produit dans la base de données
        $produit = $entityManager->getRepository(Produit::class)->find($id);

        // Vérifier si le produit existe
        if (!$produit) {
            return new JsonResponse(['error' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Modifier le prix du produit
        $nouveauPrix = $data['price'];

        if ($nouveauPrix <= 0) {
            return new JsonResponse(['error' => 'Le nouveau prix doit être positif'], Response::HTTP_BAD_REQUEST);
        }

        $produit->setPrix($nouveauPrix);

        // Enregistrer les modifications dans la base de données
        $entityManager->flush();

        // Retourner une réponse indiquant que le prix du produit a été modifié avec succès
        return new JsonResponse(['message' => 'Prix du produit modifié avec succès'], Response::HTTP_OK);
    }
}
