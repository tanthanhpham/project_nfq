<?php

namespace App\Controller\Api;

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
    private $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @Rest\Get("/products")
     */
    public function getProducts(): Response
    {
        $products = $this->productRepository->findAll();

        $serializer = SerializerBuilder::create()->build();
        $convertToJson = $serializer->serialize($products, 'json', SerializationContext::create()->setGroups('show'));
        $products = $serializer->deserialize($convertToJson, 'array', 'json');

        return $this->handleView($this->view($products));
    }

}