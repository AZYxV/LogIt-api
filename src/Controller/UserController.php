<?php

namespace App\Controller;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request, ValidatorInterface $validator, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try
        {

            $user = new User;

            $user->setPseudo($data['pseudo'] ?? null);
            $user->setEmail($data['email'] ?? null);
            $user->setPassword($data['password'] ?? null);

            $birthday = $data['birthday'] ?? '';
            $parsedDate = date_create($birthday);

            if (!$parsedDate) {
                return new JsonResponse(['message' => 'La date de naissance est invalide.'], 400);
            }
            $user->setBirthday($parsedDate);

            $user->setFirstname($data['firstname'] ?? null);
            $user->setLastname($data['lastname'] ?? null);
            $user->setRoles(['ROLE_USER']);
            $user->setCreatedAt(new \DateTimeImmutable());

            $errors = $validator->validate($user);

            if (count($errors) > 0) {
                $errorMessages = [];

                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }

                return new JsonResponse(['code' => Response::HTTP_BAD_REQUEST, 'status' => 'error', 'message' => $errorMessages], Response::HTTP_BAD_REQUEST);
            }

            $user->setPassword($passwordHasher->hashPassword($user, $data['password']) ?? null);

            $em->persist($user);
            $em->flush();

            return new JsonResponse(['code' => Response::HTTP_CREATED,'status' => 'success'],Response::HTTP_CREATED);
        }
        catch (\Exception $e)
        {
            return new JsonResponse(['code' => Response::HTTP_INTERNAL_SERVER_ERROR, 'status' => 'error', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
