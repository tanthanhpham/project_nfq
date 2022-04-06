<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\ProductItem;
use App\Event\OrderEvent;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\MailerService;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Monolog\Handler\SendGridHandler;
use SendGrid;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\Mailer\Bridge\Sendgrid\Transport\SendgridApiTransport;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

class HomeController extends AbstractFOSRestController
{
    public const PRODUCT_PAGE_LIMIT = 12;
    public const PRODUCT_PAGE_OFFSET = 0;
    private $productRepository;
    private $categoryRepository;
    private $eventDispatcher;

    public function __construct(ProductRepository $productRepository, CategoryRepository $categoryRepository, EventDispatcherInterface $eventDispatcher)
    {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @Rest\Get("/products")
     */
    public function getProducts(Request $request): Response
    {
        $limit = $request->get('limit', self::PRODUCT_PAGE_LIMIT);
        $offset = $request->get('offset', self::PRODUCT_PAGE_OFFSET);
        $products = $this->productRepository->findBy(['deletedAt' => null], ['createdAt' => 'ASC'], $limit, $offset);

        $productsList = array_map('self::dataTransferObject', $products);

        return $this->handleView($this->view($productsList, Response::HTTP_OK));
    }

    /**
     * @Rest\Get("/products/{id}")
     */
    public function getProduct(Product $product): Response
    {
        $product = self::dataTransferProductObject($product);

        return $this->handleView($this->view($product, Response::HTTP_OK));
    }

    /**
     * @Rest\Post("/products/filter")
     */
    public function filter(Request $request): Response
    {
        $limit = $request->get('limit', self::PRODUCT_PAGE_LIMIT);
        $offset = $request->get('offset', self::PRODUCT_PAGE_OFFSET);
        $requestData = json_decode($request->getContent(), true);
        $key = 'createdAt';
        $orderBy = 'DESC';
        if (($requestData['sort']) != '')
        {
            $stringSort = explode('-',$requestData['sort']);
            $key = $stringSort[0];
            $orderBy = $stringSort[1];
        }

        $products = $this->productRepository->filter($requestData, [$key => $orderBy], $limit, $offset);
        $products = array_map('self::dataTransferObject', $products);

        return $this->handleView($this->view($products, Response::HTTP_OK));
    }

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

    /**
     * @Rest\Get ("/email")
     * @param MailerInterface $mailer
     * @return Response
     */
    public function sendMail(MailerService $mailerService): Response
    {
        $order = new Order();
        $event = new OrderEvent($order);
        $this->eventDispatcher->dispatch($event);

        return $this->handleView($this->view(['success' => 'Send successfully']));
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
        $formattedProduct['image'] = $product->getImages();
        $formattedProduct['category'] = $product->getCategory()->getName();
        $formattedProduct['price'] = $product->getPrice();
        $formattedProduct['color'] = $product->getColor();

        return $formattedProduct;
    }

    /**
     * @param Product $product
     * @return array
     */
    private function dataTransferProductObject(Product $product): array
    {
        $formattedProduct = [];

        $formattedProduct['id'] = $product->getId();
        $formattedProduct['name'] = $product->getName();
        $formattedProduct['description'] = $product->getDescription();
        $formattedProduct['category'] = $product->getCategory()->getName();
        $formattedProduct['price'] = $product->getPrice();
        $formattedProduct['color'] = $product->getColor();
        $formattedProduct['material'] = $product->getMaterial();
        $formattedProduct['images'] = $product->getImages();

        $items = $product->getProductItems();
        foreach ($items as $item) {
            $formattedProduct['items'][] =  $this->dataTransferItemObject($item);
        }

        return $formattedProduct;
    }

    /**
     * @param ProductItem $productItem
     * @return array
     */
    private function dataTransferItemObject(ProductItem $productItem): array
    {
        $item = [];
        $item['id'] = $productItem->getId();
        $item['amount'] = $productItem->getAmount();
        $item['size'] = $productItem->getSize()->getName();

        return $item;
    }
}
