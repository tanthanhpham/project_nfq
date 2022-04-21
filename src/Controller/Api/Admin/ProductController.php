<?php

namespace App\Controller\Api\Admin;

use App\Controller\Api\BaseController;
use App\Entity\Gallery;
use App\Entity\Product;
use App\Entity\ProductItem;
use App\Form\ProductType;
use App\Form\ProductUpdateType;
use App\Repository\ProductItemRepository;
use App\Repository\ProductRepository;
use App\Repository\SizeRepository;
use App\Service\FileUploader;
use App\Service\HandleDataOutput;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @IsGranted("ROLE_ADMIN")
 */
class ProductController extends BaseController
{

    /**
     * @Rest\Get("/admin/products")
     */
    public function getProducts(Request $request): Response
    {
        $products = $this->productRepository->findByConditions(['deletedAt' => null], ['createdAt' => 'DESC']);

        $products['data'] = array_map('self::dataTransferProductObject', $products['data']);

        return $this->handleView($this->view($products, Response::HTTP_OK));
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
        $requestData = $request->request->all();
        $form->submit($requestData);
        if ($form->isSubmitted() && $form->isValid()) {
            $product->setUpdateAt(new \DateTime());

            $gallery = $request->files->get('images');
            $imagesPath = [];
            foreach ($gallery as $image) {
                $saveFile = $fileUploader->upload($image);
                $imagesPath[] = $saveFile;
            }

            $product->setImages($imagesPath);

            $productItemsData = (json_decode($requestData['productItems'][0], true));
            foreach ($productItemsData as $productItemData) {
                $productItem = new ProductItem();
                $size = $this->sizeRepository->find($productItemData['size']);
                $productItem->setSize($size);
                $productItem->setProduct($product);
                $productItem->setAmount($productItemData['amount']);
                $product->addProductItem($productItem);
            }
            $this->productRepository->add($product);

            return $this->handleView($this->view(['message' => 'Add product successfully'], Response::HTTP_CREATED));
        }
        $errorsMessage = $this->getFormErrorMessage($form);

        return $this->handleView($this->view($errorsMessage, Response::HTTP_BAD_REQUEST));
    }

    /**
     * @Rest\Post("admin/products/{id}")
     * @param Request $request
     * @param Product $product
     * @return Response
     */
    public function updateProduct(Product $product, Request $request, FileUploader $fileUploader): Response
    {
        $oldImages = $product->getImages();
        $form = $this->createForm(ProductUpdateType::class, $product);
        $form->submit($request->request->all());

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setUpdateAt(new \DateTime());
            $gallery = $request->files->get('images');
            $imagesPath = [];
            if (!empty($gallery)) {
                foreach ($gallery as $image) {
                    $saveFile = $fileUploader->upload($image);
                    $imagesPath[] = $saveFile;
                }
                $product->setImages($imagesPath);
            }
            $product->setImages($oldImages);
            $this->productRepository->add($product);

            return $this->handleView($this->view([], Response::HTTP_NO_CONTENT));
        }
        $errorsMessage = $this->getFormErrorMessage($form);

        return $this->handleView($this->view($errorsMessage, Response::HTTP_BAD_REQUEST));
    }

    /**
     * @Rest\Get("admin/products/{id}/productItems")
     * @param Product $product
     * @return Response
     */
    public function getProductItems(Product $product): Response
    {
        $productItems = $product->getProductItems();

        $serializer = SerializerBuilder::create()->build();
        $convertToJson = $serializer->serialize($productItems, 'json', SerializationContext::create()->setGroups(array('showProductItems')));
        $productItems = $serializer->deserialize($convertToJson, 'array', 'json');

        return $this->handleView($this->view($productItems, Response::HTTP_OK));
    }

    /**
     * @Rest\Put("admin/products/{id}/productItem")
     * @param Request $request
     * @param Product $product
     * @return Response
     */
    public function updateProductItem(Product $product, Request $request): Response
    {
        $requestData = json_decode($request->getContent(), true);
        foreach ($requestData as $productItemData) {
            $productItem = $this->productItemRepository->findOneBy(['product' => $product->getId(), 'size' => $productItemData['size']]);
            $size = $this->sizeRepository->find($productItemData['size']);
            $productItem->setSize($size);
            $productItem->setAmount($productItemData['amount']);
            $product->addProductItem($productItem);
        }
        $this->productRepository->add($product);
        return $this->handleView($this->view([], Response::HTTP_NO_CONTENT));
    }

    /**
     * @Rest\Delete("admin/products/{id}")
     * @param int $id
     * @return Response
     */
    public function deleteProduct(int $id): Response
    {
        try {
            $user = $this->productRepository->find($id);
            if (!$user) {
                return $this->handleView($this->view(
                    ['error' => 'No product was found with this id.'],
                    Response::HTTP_NOT_FOUND
                ));
            }

            $user->setDeletedAt(new \DateTime());
            $this->productRepository->add($user);

            return $this->handleView($this->view([], Response::HTTP_NO_CONTENT));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $this->handleView($this->view(
            ['error' => 'Something went wrong! Please contact support.'],
            Response::HTTP_INTERNAL_SERVER_ERROR
        ));
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
        $formattedProduct['category'] = $product->getCategory()->getName();
        $formattedProduct['description'] = $product->getDescription();
        $formattedProduct['price'] = $product->getPrice();
        $formattedProduct['color'] = $product->getColor();
        $formattedProduct['material'] = $product->getMaterial();
        $formattedProduct['images'] = self::formatImages($product->getImages());

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

    private function formatImages(array $arrImages): array
    {
        $images = [];
        foreach ($arrImages as $image)
        {
            $images[] = $this->domain . self::PATH . $image;
        }

        return $images;
    }
}
