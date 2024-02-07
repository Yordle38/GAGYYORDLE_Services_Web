<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Magasin;

class MagasinController extends AbstractController
{
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

    #[Route('/magasins/add/{nom}/{lieu}/{latitude}/{longitude}', name: 'ajouter_magasin', methods: ['POST'])]
    public function ajouter(string $nom, string $lieu, float $latitude, float $longitude, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($nom === null || $lieu === null || $latitude === null || $longitude === null) {
            return new JsonResponse(['error' => 'Le nom, le lieu, la latitude et la longitude du magasin sont obligatoires'], Response::HTTP_BAD_REQUEST);
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

}
