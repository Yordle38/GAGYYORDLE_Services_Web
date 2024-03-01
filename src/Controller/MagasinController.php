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


class MagasinController extends AbstractController
{
    // Récupérer la liste des magasins
    #[Route('/magasins', name: 'api_magasins_liste')]
    public function liste(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            
            // RECUPERE LES INFROMATIONS DU GET
            $page = $request->query->getInt('page', 1);
            $size = $request->query->getInt('size', 10);
            $offset = $page * $size;

            // RECUPERE LES MAGASINS COMPRIS ENTRE PAGE*SIZE ET PAGE*SIZE + 10
            $magasins = $entityManager->getRepository(Magasin::class)->findBy([], null, $size, $offset);


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
    public function ajouter(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
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

}
