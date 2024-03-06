<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

use App\Entity\Magasin;
use App\Entity\Stock;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;



class MagasinController extends AbstractController
{


    // Récupérer la liste des magasins
    #[Route('/magasins', name: 'api_magasins_liste')]
    public function liste(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            
            // RECUPERE LES INFROMATIONS DU GET
            $page = $request->query->getInt('page', 1);
            $size = $request->query->getInt('limit', 10);

            $longitude = $request->query->get('longitude');
            $latitude = $request->query->get('latitude');

            $offset = $page * $size;

            // RECUPERE LES MAGASINS COMPRIS ENTRE PAGE*SIZE ET PAGE*SIZE + 10
            $magasins = $entityManager->getRepository(Magasin::class)->findMagasinsProches($latitude, $longitude, $size, $offset);
            // $magasins = $entityManager->getRepository(Magasin::class)->findBy([], null, $size, $offset);

            $magasinsArray = [];

            // SET LES MAGASINS POUR LE JSON
            foreach ($magasins as $magasin) {
                $magasinsArray[] = [
                    'id' => $magasin->getId(),
                    'nom' => $magasin->getNom(),
                    'lieu' => $magasin->getLieu(),
                    'longitude' => $magasin->getLongitude(),
                    'latitude' => $magasin->getLatitude(),
                ];
            }

            if (empty($magasinsArray)) {
                return new JsonResponse(['message' => 'Il n\'y a pas de magasin'], Response::HTTP_OK);
            }

            return new JsonResponse($magasinsArray);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Une erreur s\'est produite'], Response::HTTP_CONFLICT);
        }
    }

    // Ajoute un magasin
    #[Route('/magasins/add', name: 'ajouter_magasin', methods: ['POST'])]
    public function ajouter(Request $request, EntityManagerInterface $entityManager, JWTTokenManagerInterface $jwtManager): JsonResponse
    {

        // Récupérer le token d'authentification depuis l'en-tête Authorization
        $token = $request->headers->get('Authorization');


        // Vérifier si le token est présent
        if (!$token) {
            return new JsonResponse(['error' => 'Token manquant dans l\'en-tête Authorization'], Response::HTTP_UNAUTHORIZED);
        }

        // Extraire le token JWT de l'en-tête Authorization
        $jwtToken = str_replace('Bearer ', '', $token);

        // Vérifier si le token est valide et extraire les données de l'utilisateur
        try {
            $tokenData = $jwtManager->decode($jwtToken);

            // Vérifier si l'utilisateur a le rôle "admin"
            if (!in_array('ROLE_ADMIN', $tokenData['roles'], true)) {
                return new JsonResponse(['error' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
            }
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Token invalide'], Response::HTTP_UNAUTHORIZED);
        }

        // Récupérer les données du corps de la requête
        $data = json_decode($request->getContent(), true);

        // Vérifier si les données obligatoires sont présentes
        if (!isset($data['nom'], $data['lieu'], $data['latitude'], $data['longitude'])) {
            return new JsonResponse(['error' => 'Le nom, le lieu, la latitude et la longitude du magasin sont obligatoires'], Response::HTTP_BAD_REQUEST);
        }

        $nom = $data['nom'];
        $lieu = $data['lieu'];
        $latitude = $data['latitude'];
        $longitude = $data['longitude'];

        // Vérifier que $nom est une chaîne de caractères
        if (!is_string($nom)) {
            return new JsonResponse(['error' => 'Le nom du magasin doit être une chaîne de caractères'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier que $lieu est une chaîne de caractères
        if (!is_string($lieu)) {
            return new JsonResponse(['error' => 'Le lieu du magasin doit être une chaîne de caractères'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier que $latitude est un nombre décimal (float)
        if (!is_float($latitude)) {
            return new JsonResponse(['error' => 'La latitude du magasin doit être un nombre décimal (float)'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier que $longitude est un nombre décimal (float)
        if (!is_float($longitude)) {
            return new JsonResponse(['error' => 'La longitude du magasin doit être un nombre décimal (float)'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $magasin = new Magasin();
            $magasin->setNom($nom);
            $magasin->setLieu($lieu);
            $magasin->setLatitude($latitude);
            $magasin->setLongitude($longitude);

            $entityManager->persist($magasin);
            $entityManager->flush();

            return new JsonResponse(['message' => 'Magasin ajouté avec succès', 'magasin' => $magasin->toArray()], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Une erreur s\'est produite lors de l\'ajout du magasin'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

// Récupérer le stock d'un produit dans un magasin
    #[Route('/magasins/{idMagasin}/stocks/{idProduit}', name: 'recuperer_stock_produit', methods: ['GET'])]
    public function getStockDeProduit(string $idMagasin, string $idProduit, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // Valider que idMagasin et idProduit sont des nombres
            if (!ctype_digit($idMagasin) || !ctype_digit($idProduit)) {
                return new JsonResponse(['error' => 'Les identifiants du magasin et du produit doivent être des nombres'], JsonResponse::HTTP_BAD_REQUEST);
            }

            // Convertir idMagasin et idProduit en entiers
            $idMagasin = (int) $idMagasin;
            $idProduit = (int) $idProduit;

            // Récupérer le stock du produit dans le magasin spécifique
            $stock = $entityManager->getRepository(Stock::class)->findOneBy(['magasin' => $idMagasin, 'produit' => $idProduit]);

            if (!$stock) {
                return new JsonResponse(['error' => 'Stock non trouvé'], Response::HTTP_NOT_FOUND);
            }

            // Vérifier si le stock du produit est disponible
            $estEnStock = $stock->getQuantite() > 0;

            return new JsonResponse(['estEnStock' => $estEnStock, 'quantiteDisponible' => $stock->getQuantite()]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Une erreur s\'est produite'], Response::HTTP_CONFLICT);
        }
    }

    // Récupérer tous les produits d'un magasin
    #[Route('/magasins/{idMagasin}/produits', name: 'recuperer_produits_magasin', methods: ['GET'])]
    public function getProduitsMagasin(Request $request, string $idMagasin, EntityManagerInterface $entityManager): JsonResponse
    {
        try {

            if (!ctype_digit($idMagasin)) {
                return new JsonResponse(['error' => 'L\'identifiant du magasin doit être un nombre'], JsonResponse::HTTP_BAD_REQUEST);
            }

            // Convertir idMagasin en un entier
            $idMagasin = (int) $idMagasin;

            $page = $request->query->getInt('page', 1);
            $size = $request->query->getInt('size', 10);
            $offset = $page * $size;

            // Récupérer le magasin à partir de son identifiant
            $magasin = $entityManager->getRepository(Magasin::class)->find($idMagasin);

            if (!$magasin) {
                return new JsonResponse(['error' => 'Magasin non trouvé'], JsonResponse::HTTP_NOT_FOUND);
            }

            // Récupérer les produits du magasin
            $stocks = $magasin->getStocks();
            $produits = [];
            foreach ($stocks as $stock) {
                $produits[] = $stock->getProduit()->getNom();
            }
            return new JsonResponse($produits);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Une erreur s\'est produite: ' . $e->getMessage()], JsonResponse::HTTP_CONFLICT);
        }
    }
}
