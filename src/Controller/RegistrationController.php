<?php

namespace App\Controller;

use App\Entity\Client;
use App\Form\RegistrationFormType;
use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, LoginFormAuthenticator $authenticator, EntityManagerInterface $entityManager): Response
    {

        $user = new Client();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $user->addRole('ROLE_USER');


            $entityManager->persist($user);
            $entityManager->flush();
            
            // Retourne un message de succes
            return new JsonResponse(['message' => 'Inscription réussie via le formulaire mais pas postman :/'], JsonResponse::HTTP_CREATED);
            
            // return $userAuthenticator->authenticateUser(
            //     $user,
            //     $authenticator,
            //     $request
            // );
        }
        else if ($request->isMethod('POST')) {



            $content = $request->getContent();
            $data = json_decode($content, true);


            $email = $data['email'];
            $plainPassword = $data['mot_de_passe'];


            $requiredKeys = ['email', 'mot_de_passe'];
            foreach ($requiredKeys as $key) {
                if (!isset($data[$key])) {
                    return new JsonResponse(['error' => "La cle '$key' est manquante dans les donnees JSON"], JsonResponse::HTTP_BAD_REQUEST);
                }
            }
            if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return new JsonResponse(['error' => "Ladresse email doit etre une chaine de caracteres valide"], JsonResponse::HTTP_BAD_REQUEST);
            }
        
            if (!is_string($plainPassword)) {
                return new JsonResponse(['error' => "Le mot de passe doit etre une chaine de caracteres"], JsonResponse::HTTP_BAD_REQUEST);
            }
            $user->setEmail($email);

            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $plainPassword
                )
            );

            $user->addRole('ROLE_USER');

            // Enregistrez les modifications dans la base de données
            $entityManager->persist($user);
            $entityManager->flush();

            // Enregistrer l'utilisateur en base de données
            $entityManager->persist($user);
            $entityManager->flush();


            return new JsonResponse([
                'message' => 'Inscription reussie',
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                ]
            ], JsonResponse::HTTP_CREATED);
        }
        

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
