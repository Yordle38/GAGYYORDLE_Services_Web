<?php

namespace App\Controller;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Commande;
use App\Entity\Magasin;
use App\Entity\Produit;
use App\Entity\CommandeProduit;
use App\Entity\Creneau;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class CommandeController extends AbstractController
{
    #[Route('/commandes/creer', name: 'creationCommande')]
    public function creerCommande(Request $request, EntityManagerInterface $entityManager): Response
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
            if (!array_key_exists('produits', $data)) {
                return new JsonResponse(['error' => 'La commande doit cotnenir des produits'], Response::HTTP_BAD_REQUEST);
            }
            elseif(!array_key_exists('magasin_id', $data)) {
                return new JsonResponse(['error' => 'La commande doit cotnenir un magasin_id'], Response::HTTP_BAD_REQUEST);
            }

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
            $commande->setMagasin($magasin);
            $commande->setUtilisateur($client);

            // AJOUTE LES PRODUITS A LA COMMANDE
            foreach ($produits as $produitData) {
                if (!array_key_exists('produit_id', $produitData)) {
                    return new JsonResponse(['error' => 'La cle produit_id est absente'], Response::HTTP_BAD_REQUEST);
                }
                if (!array_key_exists('quantite', $produitData)) {
                    return new JsonResponse(['error' => 'La cle quantite est absente'], Response::HTTP_BAD_REQUEST);
                }
                $produitId = $produitData['produit_id'];
                $quantite = $produitData['quantite'];

                if (!is_int($produitId)) {
                    return new JsonResponse(['error' => 'Lidentifiant du produit doit être un entier'], Response::HTTP_BAD_REQUEST);
                }
                elseif(!is_int($quantite)){
                    return new JsonResponse(['error' => 'La quantite doit etre un entier'], Response::HTTP_BAD_REQUEST);
                }


                // recupere le produit
                $produit = $entityManager->getRepository(Produit::class)->find($produitId);

                // Vérifie si le produit existe
                if (!$produit) {
                    return new JsonResponse([
                        'message' => 'Produit pas trouvé avec l\'id '.$produitId,
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
                'message' => 'creation de la commande reussie',
                'commande_id' => $commande->getId(),
                'client' => [
                    'id' => $client->getId(),
                    'email' => $client->getEmail(),
                ],
                'produits' => [],
            ];
            

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
        else {
            return new JsonResponse(['error' => 'La requete doit etre en methode post'], Response::HTTP_BAD_REQUEST);
        }
    }



    #[Route('/commandes/{idCommande}/choisir-creneau', name: 'choixCreneau')]
    public function choisirCreneau(Request $request, int $idCommande, EntityManagerInterface $entityManager): Response
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

            $client = $entityManager->getRepository(Client::class)->findOneBy(['email' => $$jwtPayload->username]);
            if(!$client){
                return new JsonResponse(['error' => 'Ce client  n\'existe pas ou plus '], Response::HTTP_BAD_REQUEST);
            }

            // recupere la commande
            $commande = $entityManager->getRepository(Commande::class)->find($idCommande);
            if(!$commande){
                return new JsonResponse(['error' => 'Cette commande nexiste pas'], Response::HTTP_BAD_REQUEST);
            }
           
            if($commande->getCreneau()){
                return new JsonResponse(['error' => 'Un creneau est deja reserve pour cette commande','idCreneau' => $commande->getCreneau()->getId()], Response::HTTP_BAD_REQUEST);
            }

            $content = $request->getContent();
            $data = json_decode($content, true);
            
            if (!array_key_exists('produit_id', $data)) {
                return new JsonResponse(['error' => 'La cle creneau_id est absente'], Response::HTTP_BAD_REQUEST);
            }

            $idCreneau = $data['creneau_id'];

            $creneau = $entityManager->getRepository(Creneau::class)->find($idCreneau);
            if(!$creneau){
                return new JsonResponse(['error' => 'Ce creneau nexiste pas'], Response::HTTP_BAD_REQUEST);
            }
            if($commande->getMagasin()!=$creneau->getMagasin()){
                return new JsonResponse(['error' => 'Ce magasin ne dispose pas de ce creneau'], Response::HTTP_BAD_REQUEST);
            }
            if($creneau->getCommande()){
                return new JsonResponse(['error' => 'Ce creneau est deja reserve pour une autre commande'], Response::HTTP_BAD_REQUEST);
            }

            $commande->setCreneau($creneau);
            $entityManager->persist($commande);
            $entityManager->flush();
            
            $responseData = [
                'message' => 'La commande est bien reservee a ce creneau',
                'creneau: ' => $creneau,
                'produits' => [],
            ];


            return new JsonResponse($responseData);
        }
        else {
            return new JsonResponse(['error' => 'La requete doit etre en methode post'], Response::HTTP_BAD_REQUEST);
        }
    }
}
