<?php

namespace App\Controller;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Commande;
use App\Entity\Magasin;
use App\Entity\Produit;
use App\Entity\CommandeProduit;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class CommandeController extends AbstractController
{
    #[Route('/commandes/creer', name: 'crationCommande')]
    public function creerCommande(Request $request, EntityManagerInterface $entityManager,  JWTTokenManagerInterface $jwtManager): Response
    {
        if ($request->isMethod('POST')) {
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
            
            $mailUser=$jwtPayload->username;

            // RECUPERE LES DONNEES DU POST
            $content = $request->getContent();
            $data = json_decode($content, true);
            $produits = $data['produits'];
            $idMagasin =  $data['magasin_id'];

            // TRAITEMENT DES ERREURS
            if (!is_int($idMagasin)) {
                return new JsonResponse(['error' => 'L\'identifiant du magasin doit être un entier'], Response::HTTP_BAD_REQUEST);
            }
            
            $magasin = $entityManager->getRepository(Magasin::class)->find($idMagasin);
            if(!$magasin){
                return new JsonResponse(['error' => 'Le magasin n\'existe pas '], Response::HTTP_BAD_REQUEST);
            }

            $client = $entityManager->getRepository(Client::class)->findOneBy(['email' => $mailUser]);
            if(!$client){
                return new JsonResponse(['error' => 'Ce client  n\'existe pas ou plus '], Response::HTTP_BAD_REQUEST);
            }

            $commande = new commande();

            $commande->setUtilisateur($client);

            // AJOUTE LES PRODUITS A LA COMMANDE
            foreach ($data['produits'] as $produitData) {
                $produitId = $produitData['produit_id'];
                $quantite = $produitData['quantite'];

                // recupere le produit
                $produit = $entityManager->getRepository(Produit::class)->find($produitId);

                // Vérifier si le produit existe
                if (!$produit) {
                    return new JsonResponse([
                        'message' => 'Produit pas trouvé avec cet id'.$produitId,
                        'status_code' => Response::HTTP_NOT_FOUND
                    ], Response::HTTP_NOT_FOUND);
                }

                // créer un commandeProduit et l'ajoute à la commande
                $commandeProduit = new CommandeProduit();
                $commandeProduit->setProduit($produit);
                $commandeProduit->setQuantite($quantite);

                $entityManager->persist($commandeProduit);

                $commande->addCommandeProduit($commandeProduit);
            }

            $entityManager->persist($commande);
            $entityManager->flush();

            $responseData = [
                'success' => true,
                'message' => 'cration de la commande reussie',
                'commande_id' => $commande->getId(),
                'client' => [
                    'id' => $client->getId(),
                    'email' => $client->getEmail(),
                ],
                'produits' => [],
            ];
            
            // Boucle à travers les produits de la commande et ajoutez-les au tableau de réponse
            foreach ($commande->getCommandeProduits() as $commandeProduit) {
                $produit = $commandeProduit->getProduit();
                $responseData['produits'][] = [
                    'id' => $produit->getId(),
                    'nom' => $produit->getNom(),
                ];
            }
            
            // Retourne une réponse JSON avec les données
            return new JsonResponse($responseData);


        }
    }
}
