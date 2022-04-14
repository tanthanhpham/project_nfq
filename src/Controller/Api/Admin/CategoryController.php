<?php

namespace App\Controller\Api\Admin;

use App\Controller\Api\BaseController;
use App\Repository\CategoryRepository;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends BaseController
{
    /**
     * @Rest\Get("/categories")
     */
    public function getCategories(): Response
    {
        $categories = $this->categoryRepository->findAll();

        $serializer = SerializerBuilder::create()->build();
        $convertToJson = $serializer->serialize($categories, 'json', SerializationContext::create()->setGroups(array('show')));
        $categories = $serializer->deserialize($convertToJson, 'array', 'json');

        return $this->handleView($this->view($categories, Response::HTTP_OK));
    }
}
