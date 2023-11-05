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
use JMS\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use App\Repository\ProductRepository;
use App\Entity\Product;

class ProductController extends AbstractController
{
    /**
     * Cette méthode permet de récupérer l'ensemble des produits.
     *
     * @OA\Tag(name="Products")
     * @OA\Parameter(
     *     name="page",
     *     in="query",
     *     description="La page que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
     * @OA\Parameter(
     *     name="limit",
     *     in="query",
     *     description="Le nombre de produits que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
     * @OA\Response(
     *     response=200,
     *     description="Retourne la liste des produits",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Product::class))
     *     )
     * )
     *
     * @param Request $request request
     * @param ProductRepository $productRepository product repository
     * @param SerializerInterface $serializer serializer
     * @param TagAwareCacheInterface $cache cache
     *
     * @return JsonResponse
     */
    #[Route('/api/products', name: 'products', methods: ['GET'])]
    public function getProducts(
        Request $request,
        ProductRepository $productRepository,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cache
    ): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $cacheId = 'getProducts/'.$page.'-'.$limit;
        $jsonProducts = $cache->get($cacheId, function(ItemInterface $item) use ($productRepository, $page, $limit, $serializer) {
            $item->tag('productsCache');

            $products = $productRepository->findAllWithPagination($page, $limit);
            return $serializer->serialize($products, 'json');
        });

        return new JsonResponse($jsonProducts, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    /**
     * Cette méthode permet de récupérer un produit par son id.
     *
     * @OA\Tag(name="Products")
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Id du produit que l'on veut récupérer",
     *     @OA\Schema(type="int")
     * )
     * @OA\Response(
     *     response=200,
     *     description="Retourne un produit",
     *     @OA\JsonContent(
     *        ref=@Model(type=Product::class)
     *     )
     * )
     * @OA\Response(
     *     response=404,
     *     description="Le produit n'existe pas",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="status", type="int", example=404),
     *        @OA\Property(property="message", type="string", example="Le produit n'existe pas.")
     *     )
     * )
     *
     * @param Product $product product
     * @param SerializerInterface $serializer serializer
     * @param TagAwareCacheInterface $cache cache
     *
     * @return JsonResponse
     */
    #[Route('/api/products/{id}', name: 'product', methods: ['GET'])]
    public function getProductById(
        Product $product,
        SerializerInterface $serializer,
        TagAwareCacheInterface $cache
    ): JsonResponse
    {
        $cacheId = 'getProductById/'.$product->getId();
        $jsonProduct = $cache->get($cacheId, function(ItemInterface $item) use ($product, $serializer) {
            $item->tag('productsCache');

            return $serializer->serialize($product, 'json');
        });

        return new JsonResponse($jsonProduct, Response::HTTP_OK, ['accept' => 'json'], true);
    }
}
