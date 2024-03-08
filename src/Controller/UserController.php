<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class UserController extends AbstractController
{
    #[Route('/inscription', name: 'inscription', methods: ['POST'])]
    public function inscription(Request $request, UserPasswordEncoderInterface $passwordEncoder, EntityManagerInterface $entityManager): JsonResponse
    {
        // R�cup�rer les donn�es du corps de la requ�te
        $data = json_decode($request->getContent(), true);

        // V�rifier si les donn�es obligatoires sont pr�sentes
        if (!isset($data['email'], $data['password'])) {
            return new JsonResponse(['error' => 'L\'email et le mot de passe sont obligatoires'], Response::HTTP_BAD_REQUEST);
        }

        $email = $data['email'];
        $password = $data['password'];

        // Cr�er un nouvel utilisateur
        $user = new User();
        $user->setEmail($email);

        // Encoder le mot de passe
        $encodedPassword = $passwordEncoder->encodePassword($user, $password);
        $user->setPassword($encodedPassword);

        // Enregistrer l'utilisateur dans la base de donn�es
        try {
            $entityManager->persist($user);
            $entityManager->flush();

            return new JsonResponse(['message' => 'Inscription r�ussie'], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Une erreur s\'est produite lors de l\'inscription'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
