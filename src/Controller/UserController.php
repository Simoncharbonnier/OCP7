<?php

namespace App\Controller;

use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use App\Repository\UserRepository;
use App\Entity\User;
use App\Repository\ClientRepository;

class UserController extends AbstractController
{
    /**
     * Cette méthode permet de récupérer l'ensemble des utilisateurs liés au client authentifié.
     *
     * @OA\Tag(name="Users")
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="La page que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
     * @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="Le nombre d'utilisateurs que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
     * @OA\Response(
     *     response=200,
     *     description="Retourne la liste des utilisateurs liés à un client",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=User::class, groups={"getUsers"}))
     *     )
     * )
     * @OA\Response(
     *     response=401,
     *     description="Vous n'avez pas les permissions nécessaires.",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="code", type="int", example=401),
     *        @OA\Property(property="message", type="string", example="JWT Token not found.")
     *     )
     * )
     *
     * @param Request $request request
     * @param UserRepository $userRepository user repository
     * @param SerializerInterface $serializer serializer
     * @param TagAwareCacheInterface $cache cache
     *
     * @return JsonResponse
     */
    #[Route('/api/users', name: 'users', methods: ['GET'])]
    public function getUsers(
        Request $request,
        UserRepository $userRepository,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cache
    ): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $cacheId = 'getUsers/'.$this->getUser()->getId().'-'.$page.'-'.$limit;
        $jsonUsers = $cache->get($cacheId, function(ItemInterface $item) use ($userRepository, $page, $limit, $serializer) {
            $item->tag(['usersCache', 'client-'.$this->getUser()->getId().'-cache']);

            $users = $userRepository->findWithPagination($this->getUser()->getId(), $page, $limit);
            $context = SerializationContext::create()->setGroups(['getUsers']);
            return $serializer->serialize($users, 'json', $context);
        });

        return new JsonResponse($jsonUsers, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    /**
     * Cette méthode permet de récupérer un utilisateur lié au client authentifié par son id.
     *
     * @OA\Tag(name="Users")
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Id de l'utilisateur que l'on veut récupérer",
     *     @OA\Schema(type="string")
     * )
     * @OA\Response(
     *     response=200,
     *     description="Retourne un utilisateur",
     *     @OA\JsonContent(
     *        ref=@Model(type=User::class, groups={"getUsers"})
     *     )
     * )
     * @OA\Response(
     *     response=403,
     *     description="L'utilisateur n'appartient pas au client authentifié",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="status", type="int", example=403),
     *        @OA\Property(property="message", type="string", example="Vous n'avez pas les droits pour accéder à ces informations.")
     *     )
     * )
     * @OA\Response(
     *     response=404,
     *     description="L'utilisateur n'existe pas",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="status", type="int", example=404),
     *        @OA\Property(property="message", type="string", example="L'utilisateur n'existe pas.")
     *     )
     * )
     * @OA\Response(
     *     response=401,
     *     description="Vous n'avez pas les permissions nécessaires.",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="code", type="int", example=401),
     *        @OA\Property(property="message", type="string", example="JWT Token not found.")
     *     )
     * )
     *
     * @param User $user user
     * @param SerializerInterface $serializer serializer
     * @param TagAwareCacheInterface $cache cache
     *
     * @return JsonResponse
     */
    #[Route('/api/users/{id}', name: 'user', methods: ['GET'])]
    public function getUserById(
        User $user,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cache
    ): JsonResponse
    {
        if ($user->getClient() !== $this->getUser()) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'Vous n\'avez pas les droits pour accéder à ces informations.');
        }

        $cacheId = 'getUserById/'.$user->getId();
        $jsonUser = $cache->get($cacheId, function(ItemInterface $item) use ($user, $serializer) {
            $item->tag(['usersCache', 'client-'.$this->getUser()->getId().'-cache']);

            $context = SerializationContext::create()->setGroups(['getUsers']);
            return $serializer->serialize($user, 'json', $context);
        });

        return new JsonResponse($jsonUser, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    /**
     * Cette méthode permet de créer un utilisateur lié au client authentifié.
     *
     * @OA\Tag(name="Users")
     * @OA\RequestBody(
     *     description="Objet de l'utilisateur qui doit être créé",
     *     required=true,
     *     @OA\JsonContent(ref=@Model(type=User::class, groups={"getUsers"}))
     * )
     * @OA\Response(
     *     response=201,
     *     description="Retourne l'utilisateur créé",
     *     @OA\JsonContent(ref=@Model(type=User::class, groups={"getUsers"}))
     * )
     * @OA\Response(
     *     response=400,
     *     description="Requête invalide",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="status", type="int", example=400),
     *        @OA\Property(property="message", type="string")
     *     )
     * )
     * @OA\Response(
     *     response=401,
     *     description="Vous n'avez pas les permissions nécessaires.",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="code", type="int", example=401),
     *        @OA\Property(property="message", type="string", example="JWT Token not found.")
     *     )
     * )
     *
     * @param Request $request request
     * @param SerializerInterface $serializer serializer
     * @param EntityManagerInterface $em entity manager
     * @param ClientRepository $clientRepository client repository
     * @param ValidatorInterface $validator validator
     * @param TagAwareCacheInterface $cache cache
     *
     * @return JsonResponse
     */
    #[Route('/api/users', name:'createUser', methods: ['POST'])]
    public function createUser(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        ClientRepository $clientRepository,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache
    ): JsonResponse
    {
        if (isset($request->toArray()['client'])) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Le client ne fait pas parti des données à fournir.');
        }

        $user = $serializer->deserialize($request->getContent(), User::class, 'json');
        $user->setClient($this->getUser());

        $errors = $validator->validate($user);
        if ($errors->count() > 0) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, $errors[0]->getMessage());
        }

        $cache->invalidateTags(['client-'.$this->getUser()->getId().'-cache']);
        $em->persist($user);
        $em->flush();

        $context = SerializationContext::create()->setGroups(['getUsers']);
        $jsonUser = $serializer->serialize($user, 'json', $context);
        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ['accept' => 'json'], true);
    }

    /**
     * Cette méthode permet de mettre à jour un utilisateur lié au client authentifié.
     *
     * @OA\Tag(name="Users")
     * @OA\RequestBody(
     *     description="Objet de l'utilisateur qui doit être mis à jour",
     *     required=true,
     *     @OA\JsonContent(ref=@Model(type=User::class, groups={"getUsers"}))
     * )
     * @OA\Response(
     *     response=200,
     *     description="Retourne l'utilisateur mis à jour",
     *     @OA\JsonContent(ref=@Model(type=User::class, groups={"getUsers"}))
     * )
     * @OA\Response(
     *     response=400,
     *     description="Requête invalide",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="status", type="int", example=400),
     *        @OA\Property(property="message", type="string")
     *     )
     * )
     * @OA\Response(
     *     response=403,
     *     description="L'utilisateur n'appartient pas au client authentifié",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="status", type="int", example=403),
     *        @OA\Property(property="message", type="string", example="Vous n'avez pas les droits pour mettre à jour ces informations.")
     *     )
     * )
     * @OA\Response(
     *     response=404,
     *     description="L'utilisateur n'existe pas",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="status", type="int", example=404),
     *        @OA\Property(property="message", type="string", example="L'utilisateur n'existe pas.")
     *     )
     * )
     * @OA\Response(
     *     response=401,
     *     description="Vous n'avez pas les permissions nécessaires.",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="code", type="int", example=401),
     *        @OA\Property(property="message", type="string", example="JWT Token not found.")
     *     )
     * )
     *
     * @param Request $request request
     * @param SerializerInterface $serializer serializer
     * @param User $currentUser current user
     * @param EntityManagerInterface $em entity manager
     * @param ClientRepository $clientRepository client repository
     * @param ValidatorInterface $validator validator
     * @param TagAwareCacheInterface $cache cache
     *
     * @return JsonResponse
     */
    #[Route('/api/users/{id}', name:'updateUser', methods:['PUT'])]
    public function updateUser(
        Request $request,
        SerializerInterface $serializer,
        User $currentUser,
        EntityManagerInterface $em,
        ClientRepository $clientRepository,
        ValidatorInterface $validator,
        TagAwareCacheInterface $cache
    ): JsonResponse
    {
        if ($currentUser->getClient() !== $this->getUser()) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'Vous n\'avez pas les droits pour mettre à jour ces informations.');
        }

        if (isset($request->toArray()['client'])) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Le client ne fait pas parti des données à fournir.');
        }

        $newUser = $serializer->deserialize($request->getContent(), User::class, 'json');
        if ($newUser->getFirstName() !== null) {
            $currentUser->setFirstName($newUser->getFirstName());
        }
        if ($newUser->getLastName() !== null) {
            $currentUser->setLastName($newUser->getLastName());
        }
        if ($newUser->getMail() !== null) {
            $currentUser->setMail($newUser->getMail());
        }
        if ($newUser->getPhone() !== null) {
            $currentUser->setPhone($newUser->getPhone());
        }

        $errors = $validator->validate($currentUser);
        if ($errors->count() > 0) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, $errors[0]->getMessage());
        }

        $cache->invalidateTags(['client-'.$this->getUser()->getId().'-cache']);
        $em->persist($currentUser);
        $em->flush();

        $context = SerializationContext::create()->setGroups(['getUsers']);
        $jsonUser = $serializer->serialize($currentUser, 'json', $context);
        return new JsonResponse($jsonUser, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    /**
     * Cette méthode permet de supprimer un utilisateur lié au client authentifié.
     *
     * @OA\Tag(name="Users")
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Id de l'utilisateur que l'on veut supprimer",
     *     @OA\Schema(type="int")
     * )
     * @OA\Response(
     *     response=204,
     *     description="L'utilisateur a été supprimé"
     * )
     * @OA\Response(
     *     response=403,
     *     description="L'utilisateur n'appartient pas au client authentifié",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="status", type="int", example=403),
     *        @OA\Property(property="message", type="string", example="Vous n'avez pas les droits pour supprimer ces informations.")
     *     )
     * )
     * @OA\Response(
     *     response=404,
     *     description="L'utilisateur n'existe pas",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="status", type="int", example=404),
     *        @OA\Property(property="message", type="string", example="L'utilisateur n'existe pas.")
     *     )
     * )
     * @OA\Response(
     *     response=401,
     *     description="Vous n'avez pas les permissions nécessaires.",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="code", type="int", example=401),
     *        @OA\Property(property="message", type="string", example="JWT Token not found.")
     *     )
     * )
     *
     * @param User $user user
     * @param EntityManagerInterface $em entity manager
     * @param TagAwareCacheInterface $cache cache
     *
     * @return JsonResponse
     */
    #[Route('/api/users/{id}', name: 'deleteUser', methods: ['DELETE'])]
    public function deleteUser(
        User $user,
        EntityManagerInterface $em,
        TagAwareCacheInterface $cache
    ): JsonResponse
    {
        if ($user->getClient() !== $this->getUser()) {
            throw new HttpException(Response::HTTP_FORBIDDEN, 'Vous n\'avez pas les droits pour supprimer ces informations.');
        }

        $cache->invalidateTags(['client-'.$this->getUser()->getId().'-cache']);
        $em->remove($user);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
