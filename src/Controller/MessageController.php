<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
namespace App\Controller;
use App\Entity\Vendeur;
use App\Entity\Message;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;


class MessageController extends AbstractController
{
    #[Route('/message', name: 'app_message')]
    public function index(): Response
    {
        return $this->render('message/index.html.twig', [
            'controller_name' => 'MessageController',
        ]);
    }
    #[Route(path: '/envoieMessage', name: 'envoieMessage', methods: ['POST'])]
    public function envoieMessage(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $vendeur = new Vendeur();
        if ($request->isMethod('POST')) {

            $message = new Message();

            $content = $request->getContent();
            $data = json_decode($content, true);
            $mailUtilisateur = $data['mailUtilisateur'];
            $contentMessage =  $data['message'];

            $idVendeur = $data['idVendeur'];
            if (!is_int($idVendeur)) {
                return new JsonResponse(['error' => 'L\'identifiant du vendeur doit être un entier'], Response::HTTP_BAD_REQUEST);
            }
            if(!is_string($mailUtilisateur)){
                return new JsonResponse(['error' => 'Le mail doit être un string'], Response::HTTP_BAD_REQUEST);
            }
            if($mailUtilisateur==""){
                return new JsonResponse(['error' => 'Le mail ne peut pas être vide'], Response::HTTP_BAD_REQUEST);
            }

            $vendeur = $entityManager->getRepository(Vendeur::class)->find($idVendeur);
            
            if (!$vendeur) {
                return new JsonResponse(['error' => 'Le vendeur n\'existe pas'], Response::HTTP_NOT_FOUND);
            }
            if(!isset($data['mailUtilisateur']) || !isset($data['message']) || !isset($data['idVendeur'])){
                return new JsonResponse(['error' => 'mailUtilisateur, message et idVendeur doivent être renseignées'], Response::HTTP_NOT_FOUND);
            }
            $message->setVendeur($vendeur);
            $message->setContenue($contentMessage);
            $message->setDateEnvoie(new \DateTime());

            // Enregistrez les modifications dans la base de données
            $entityManager->persist($message);
            $entityManager->flush();


            return new JsonResponse([
                'message' => 'Message envoyé avec succès au vendeur',
                'message' => $contentMessage,
                'vendeur: ' => [
                    'id' => $vendeur->getId(),
                    'prenom' => $vendeur->getPrenom(),
                    'nom' => $vendeur->getNom(),
                ],
                'envoyé à ' => $message->getDateEnvoie()
            ], JsonResponse::HTTP_CREATED);
        }
    }
}
?>