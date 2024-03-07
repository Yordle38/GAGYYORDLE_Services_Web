<?php

namespace App\Controller;

use App\Entity\Magasin;
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

    #[Route ('/produit/{idMagasin}/{idProduit}', name: 'ajouter_magasin', methods: ['DELETE'])]
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
}
