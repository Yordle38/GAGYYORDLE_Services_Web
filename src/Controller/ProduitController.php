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
        // R�cup�rer le token d'authentification depuis l'en-t�te Authorization
        $token = $request->headers->get('Authorization');

        // V�rifie si le token JWT est vide ou s'il ne commence pas par "Bearer "
        if (!$token || strpos($token, 'Bearer ') !== 0) {
            return new Response('non autoris�', Response::HTTP_UNAUTHORIZED);
        }
        $tokenParts = explode(".", $token);
        $tokenPayload = base64_decode($tokenParts[1]);

        // V�rifie si le d�codage a r�ussi
        if (!$tokenPayload) {
            return new Response('token non valide', Response::HTTP_UNAUTHORIZED);
        }

        $jwtPayload = json_decode($tokenPayload);

        // V�rifie si la charge utile du JWT contient l'identifiant de l'utilisateur
        if (!isset($jwtPayload->username)) {
            return new Response('Utilisateur non connect�', Response::HTTP_UNAUTHORIZED);
        }

        $tokenParts = explode(".", $token);

        $tokenPayload = base64_decode($tokenParts[1]);
        $jwtPayload = json_decode($tokenPayload);

        $role=$jwtPayload->roles[0];

        if($role != "ROLE_ADMIN"){
            return new Response('non autoris�', Response::HTTP_UNAUTHORIZED);
        }


// Recherche du magasin dans la base de donn�es
        $magasin = $entityManager->getRepository(Magasin::class)->find($idMagasin);

        // V�rifie si le magasin existe
        if (!$magasin) {
            return new Response('Magasin non trouv�', Response::HTTP_NOT_FOUND);
        }

        // Recherche du produit dans le magasin
        $stock = $entityManager->getRepository(Stock::class)->findOneBy(['magasin' => $magasin, 'produit' => $idProduit]);

        // V�rifie si le stock existe
        if (!$stock) {
            return new Response('Produit non trouv� dans ce magasin', Response::HTTP_NOT_FOUND);
        }

        // Suppression du stock du produit dans le magasin
        $entityManager->remove($stock);
        $entityManager->flush();

        // Retourne une r�ponse indiquant que la suppression a r�ussi
        return new Response('Produit supprim� du magasin avec succ�s', Response::HTTP_OK);
    }

    #[Route('/produit/ajouter/{idMagasin}/{idProduit}/{quantite}', name: 'ajouter_produit_magasin', methods: ['POST'])]
    public function ajouter($idMagasin, $idProduit, $quantite, Request $request, EntityManagerInterface $entityManager, JWTTokenManagerInterface $jwtManager): Response
    {
        // R�cup�rer le token d'authentification depuis l'en-t�te Authorization
        $token = $request->headers->get('Authorization');

        // V�rifie si le token JWT est vide ou s'il ne commence pas par "Bearer "
        if (!$token || strpos($token, 'Bearer ') !== 0) {
            return new Response('non autoris�', Response::HTTP_UNAUTHORIZED);
        }
        $tokenParts = explode(".", $token);
        $tokenPayload = base64_decode($tokenParts[1]);

        // V�rifie si le d�codage a r�ussi
        if (!$tokenPayload) {
            return new Response('token non valide', Response::HTTP_UNAUTHORIZED);
        }

        $jwtPayload = json_decode($tokenPayload);

        // V�rifie si la charge utile du JWT contient l'identifiant de l'utilisateur
        if (!isset($jwtPayload->username)) {
            return new Response('Utilisateur non connect�', Response::HTTP_UNAUTHORIZED);
        }

        $tokenParts = explode(".", $token);

        $tokenPayload = base64_decode($tokenParts[1]);
        $jwtPayload = json_decode($tokenPayload);

        $role = $jwtPayload->roles[0];

        if ($role != "ROLE_ADMIN") {
            return new Response('non autoris�', Response::HTTP_UNAUTHORIZED);
        }

        // Recherche du magasin dans la base de donn�es
        $magasin = $entityManager->getRepository(Magasin::class)->find($idMagasin);

        // V�rifie si le magasin existe
        if (!$magasin) {
            return new Response('Magasin non trouv�', Response::HTTP_NOT_FOUND);
        }

        // Recherche du produit dans la base de donn�es
        $produit = $entityManager->getRepository(Produit::class)->find($idProduit);

        // V�rifie si le produit existe
        if (!$produit) {
            return new Response('Produit non trouv�', Response::HTTP_NOT_FOUND);
        }

        // Cr�ation d'une nouvelle instance de Stock
        $stock = new Stock();
        $stock->setMagasin($magasin);
        $stock->setProduit($produit);
        $stock->setQuantite($quantite); // D�finit la quantit� du produit dans le stock

        // Ajout du stock � l'EntityManager
        $entityManager->persist($stock);
        $entityManager->flush();

        // Retourne une r�ponse indiquant que l'ajout a r�ussi
        return new Response('Produit ajout� au magasin avec succ�s', Response::HTTP_CREATED);
    }
}
