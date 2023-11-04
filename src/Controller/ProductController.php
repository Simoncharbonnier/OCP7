<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use App\Repository\ProductRepository;
use App\Entity\Product;

class ProductController extends AbstractController
{
    /**
     * Products list
     * @param Request $request request
     * @param ProductRepository $productRepository product repository
     * @param SerializerInterface $serializer serializer
     *
     * @return JsonResponse
     */
    #[Route('/products', name: 'products', methods: ['GET'])]
    public function getProducts(
        Request $request,
        ProductRepository $productRepository,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);
        $products = $productRepository->findAllWithPagination($page, $limit);

        $jsonProducts = $serializer->serialize($products, 'json');
        return new JsonResponse($jsonProducts, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    /**
     * Product detail
     * @param Product $product product
     * @param SerializerInterface $serializer serializer
     *
     * @return JsonResponse
     */
    #[Route('/products/{id}', name: 'product', methods: ['GET'])]
    public function getProductById(
        Product $product,
        SerializerInterface $serializer
    ): JsonResponse
    {
        $jsonProduct = $serializer->serialize($product, 'json');
        return new JsonResponse($jsonProduct, Response::HTTP_OK, ['accept' => 'json'], true);
    }
}
