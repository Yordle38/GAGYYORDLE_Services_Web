<?php

namespace App\Controller;

use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\CreneauRepository;

use App\Entity\Magasin;
use App\Entity\Stock;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;




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
    #[Route("/magasins/add", name: 'ajouter_magasin', methods: ['POST'])]
    public function ajouter(Request $request, EntityManagerInterface $entityManager, JWTTokenManagerInterface $jwtManager): Response
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
    public function getStockDeProduit(int $idMagasin, int $idProduit, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
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
    #[Route('/magasins/{idMagasin}/produits', name: 'recuperer_stock_produit', methods: ['GET'])]
    public function getProduitsMagasin(int $idMagasin, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            // Récupérer le magasin
            $magasin = $entityManager->getRepository(Magasin::class)->find($idMagasin);

            if (!$magasin) {
                return new JsonResponse(['error' => 'Magasin non trouvé'], Response::HTTP_NOT_FOUND);
            }

            // Récupérer tous les produits du magasin
            $produits = [];
            foreach ($magasin->getStocks() as $stock) {
                $produits[] = [
                    'id' => $stock->getProduit()->getId(),
                    'nom' => $stock->getProduit()->getNom(),
                    'prix' => $stock->getProduit()->getPrix(),
                    // Ajoutez d'autres propriétés de Produit si nécessaire
                ];
            }

            return new JsonResponse($produits);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Une erreur s\'est produite'], Response::HTTP_CONFLICT);
        }
    }

    // Récupére tous les produits d'un magasin
    #[Route('/magasins/{idMagasin}/creneaux-disponibles', name: 'recupererCreneaux', methods: ['GET'])]
    public function getCreneauxFromMagasin(int $idMagasin, EntityManagerInterface $entityManager, CreneauRepository $creneauRepository): JsonResponse
    {
        if (!is_int($idMagasin) || $idMagasin <= 0) {
            return new JsonResponse(['error' => 'L\'identifiant du magasin doit être un entier positif'], Response::HTTP_BAD_REQUEST);
        }

        // Récupére le magasin
        $magasin = $entityManager->getRepository(Magasin::class)->find($idMagasin);


        if (!$magasin) {
            return new JsonResponse(['error' => 'Magasin non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $creneaux = $creneauRepository->findByMagasin($magasin);
        $creneauxFiltres = array_filter($creneaux, function($creneau) {
            return $creneau->getCommande() === null;
        });

        
        // Converti les objets Creneau en un tableau JSON
        foreach ($creneauxFiltres as $creneau) {
            $creneauxFormatted[] = [
                'id' => $creneau->getId(),
                'heure_debut' => date_format(date_create_from_format('H:i:s', $creneau->getHeureDebut()), 'H:i:s'),
                'heure_fin' => date_format(date_create_from_format('H:i:s', $creneau->getHeureFin()), 'H:i:s'),
            ];
        }
        return new JsonResponse($creneauxFormatted);
    }


//    #[\Symfony\Component\Routing\Attribute\Route('/login', name: 'token', methods: ['POST'])]
//    public function login(EntityManagerInterface $entityManager, Request $request, UserPasswordHasherInterface $passwordHasher, JWTTokenManagerInterface $jwtManager): JsonResponse {
//        $credentials = json_decode($request->getContent(), true);
//
//
//        if ($credentials === null) {
//            return new JsonResponse(['message' => 'Les données JSON sont invalides'], 400);
//        }
//
//        if (!isset($credentials['username']) || !isset($credentials['password'])) {
//            return new JsonResponse(['message' => 'Les champs email et password sont requis'], 400);
//        }
//
//        $user = $entityManager->getRepository(Client::class)->findOneBy(['email' => $credentials['username']]);
//
//        if (!$user || !$passwordHasher->isPasswordValid($user, $credentials['password'])) {
//            return new JsonResponse(['message' => 'Identifiants invalides'], 401);
//        }
//
//        $token = $jwtManager->create($user);
//
//        return new JsonResponse(['token' => $token]);
//    }
//
//    #[Route(path: '/logout', name: 'app_logout')]
//    public function logout(): void
//    {
//        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
//    }

}
