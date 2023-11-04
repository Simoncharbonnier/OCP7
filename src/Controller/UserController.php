<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use App\Entity\User;
use App\Repository\ClientRepository;

class UserController extends AbstractController
{
    /**
     * Users list
     * @param UserRepository $userRepository user repository
     * @param SerializerInterface $serializer serializer
     *
     * @return JsonResponse
     */
    #[Route('/users', name: 'users', methods: ['GET'])]
    public function getUsers(
        UserRepository $userRepository,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $users = $userRepository->findAll();

        $jsonUsers = $serializer->serialize($users, 'json', ['groups' => 'getUsers']);
        return new JsonResponse($jsonUsers, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    /**
     * User detail
     * @param User $user user
     * @param SerializerInterface $serializer serializer
     *
     * @return JsonResponse
     */
    #[Route('/users/{id}', name: 'user', methods: ['GET'])]
    public function getUserById(
        User $user,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'getUsers']);
        return new JsonResponse($jsonUser, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    /**
     * Create user
     * @param Request $request request
     * @param SerializerInterface $serializer serializer
     * @param EntityManagerInterface $em entity manager
     * @param ClientRepository $clientRepository client repository
     * @param ValidatorInterface $validator
     *
     * @return JsonResponse
     */
    #[Route('/users', name:'createUser', methods: ['POST'])]
    public function createUser(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        ClientRepository $clientRepository,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');

        $content = $request->toArray();
        $clientId = $content['client_id'] ?? -1;
        $user->setClient($clientRepository->find($clientId));

        $errors = $validator->validate($user);
        if ($errors->count() > 0) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, $errors[0]->getMessage());
        }

        $em->persist($user);
        $em->flush();

        $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'getUsers']);
        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ['accept' => 'json'], true);
    }

    /**
     * Update user
     * @param Request $request request
     * @param SerializerInterface $serializer serializer
     * @param User $currentUser current user
     * @param EntityManagerInterface $em entity manager
     * @param ClientRepository $clientRepository client repository
     * @param ValidatorInterface $validator
     *
     * @return JsonResponse
     */
    #[Route('/users/{id}', name:'updateUser', methods:['PUT'])]
    public function updateUser(
        Request $request,
        SerializerInterface $serializer,
        User $currentUser,
        EntityManagerInterface $em,
        ClientRepository $clientRepository,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $updatedUser = $serializer->deserialize($request->getContent(),
            User::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $currentUser]);

        $errors = $validator->validate($updatedUser);
        if ($errors->count() > 0) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, $errors[0]->getMessage());
        }

        $em->persist($updatedUser);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Delete user
     * @param User $user user
     * @param EntityManagerInterface $em entity manager
     *
     * @return JsonResponse
     */
    #[Route('/users/{id}', name: 'deleteUser', methods: ['DELETE'])]
    public function deleteUser(
        User $user,
        EntityManagerInterface $em
    ): JsonResponse
    {
        $em->remove($user);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
