<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Repository\ProductRepository;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends AbstractFOSRestController
{
    public const PRODUCT_PAGE_LIMIT = 10;
    public const PRODUCT_PAGE_OFFSET = 0;
    private $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @Rest\Get("/products")
     */
    public function getProducts(Request $request): Response
    {
        $limit = $request->get('limit', self::PRODUCT_PAGE_LIMIT);
        $offset = $request->get('offset', self::PRODUCT_PAGE_OFFSET);
        $products = $this->productRepository->findBy([], [], $limit, $offset);

        $productsList = array_map('self::dataTransferObject',$products);

        return $this->handleView($this->view($productsList));
    }

    /**
     * @param Product $product
     * @return array
     */
    public function dataTransferObject(Product $product): array
    {
        $formattedProduct = [];

        $formattedProduct['id'] = $product->getId();
        $formattedProduct['name'] = $product->getName();
        $formattedProduct['image'] = $product->getImage();
        $formattedProduct['category'] = $product->getCategory()->getName();
        $formattedProduct['price'] = $product->getPrice();

        return $formattedProduct;
    }
}