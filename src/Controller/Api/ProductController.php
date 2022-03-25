<?php

namespace App\Controller\Api;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends AbstractFOSRestController
{
    /**
     * @Rest\Get("/products")
     */
    public function getProducts(): Response
    {
        $products = [
            'name' => 'Product name',
            'description' => 'Product description'
        ];

        return $this->handleView($this->view($products));
    }

}