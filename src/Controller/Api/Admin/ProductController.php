<?php

namespace App\Controller\Api\Admin;

use App\Entity\Gallery;
use App\Entity\Product;
use App\Entity\ProductItem;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Service\FileUploader;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_ADMIN")
 */
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
     * @Rest\Get("/admin/products")
     */
    public function getProducts(Request $request): Response
    {
        $limit = $request->get('limit', self::PRODUCT_PAGE_LIMIT);
        $offset = $request->get('offset', self::PRODUCT_PAGE_OFFSET);
        $products = $this->productRepository->findBy([], ['createdAt' => 'ASC'], $limit, $offset);

        $productsList = array_map('self::dataTransferObject', $products);

        return $this->handleView($this->view($productsList, Response::HTTP_OK));
    }

    /**
     * @Rest\Get("/admin/products/{id}")
     */
    public function getProduct(Product $product): Response
    {
        $product = self::dataTransferProductObject($product);

        return $this->handleView($this->view($product, Response::HTTP_OK));
    }

    /**
     * @Rest\Post("admin/products")
     * @param Request $request
     * @param FileUploader $fileUploader
     * @return Response
     */
    public function insertProduct(Request $request, FileUploader $fileUploader): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->submit($request->request->all());

        $imagePath = [];
        $images = $request->files->get('image');
        if ($images) {
            $saveFile = $fileUploader->upload($images);
            $imagePath[] = $saveFile;
        }
        if ($form->isSubmitted()) {
            $product->setImage($imagePath);
            $product->setCreateAt(new \DateTime());
            $product->setUpdateAt(new \DateTime());

            $this->productRepository->add($product);

            return $this->handleView($this->view($product, Response::HTTP_CREATED));
        }

        return $this->handleView($this->view($form->getErrors(), Response::HTTP_BAD_REQUEST));
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
        $formattedProduct['price'] = $product->getPrice();
        $formattedProduct['color'] = $product->getColor();
        $formattedProduct['material'] = $product->getMaterial();

        $gallery = $product->getGalleries();
        foreach ($gallery as $image) {
            $formattedProduct['gallery'][] =  $image->getPath();
        }

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
