<?php

namespace App\Controller\Api;

use App\Entity\Cart;
use App\Entity\Product;
use App\Entity\ProductItem;
use App\Form\CartItemType;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use App\Service\GetUserInfo;
use App\Service\HandleDataOutput;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class CartController extends AbstractFOSRestController
{
    public const CART_ITEMS_PER_PAGE = 10;
    public const CART_ITEMS_PAGE_NUMBER = 1;
    private $cartRepository;
    private $userLoginInfo;
    private $handleDataOutput;

    public function __construct(
        CartRepository $cartRepository,
        GetUserInfo $userLogin,
        HandleDataOutput $handleDataOutput
    ) {
        $this->cartRepository = $cartRepository;
        $this->userLoginInfo = $userLogin->getUserLoginInfo();
        $this->handleDataOutput = $handleDataOutput;
    }

    /**
     * @Rest\Get("/users/carts")
     * @IsGranted("ROLE_USER")
     */
    public function getCarts(Request $request): Response
    {
        try {
            $limit = intval($request->get('limit', self::CART_ITEMS_PER_PAGE));
            $page = intval($request->get('page', self::CART_ITEMS_PAGE_NUMBER));
            $offset = $limit * ($page - 1);
            $carts = $this->cartRepository->findBy(
                ['user' => $this->userLoginInfo->getId()],[],
                $limit,
                $offset
            );

            $transferData = array_map('self::dataTransferCartItemObject', $carts);
            $carts = $this->handleDataOutput->transferDataGroup($transferData, 'getCartItems');

            return $this->handleView($this->view($carts, Response::HTTP_OK));
        } catch (\Exception $e) {
            //Need to add log the error message
        }

        return $this->handleView($this->view([
            'error' => 'Something went wrong! Please contact support.'
        ],Response::HTTP_INTERNAL_SERVER_ERROR));
    }

    /**
     * @Rest\Post("/users/carts")
     * @IsGranted("ROLE_USER")
     * @param Request $request
     * @return Response
     */
    public function insertCart(Request $request): Response
    {
        try {
            $payload = json_decode($request->getContent(), true);
            $cartItem = $this->cartRepository->findOneBy([
                'productItem' => $payload['productItem'],
                'user' => $this->userLoginInfo->getId()
            ]);

            if (!$cartItem) {
                $cartItem = new Cart();
                $cartItem->setUser($this->userLoginInfo);
            } else {
                $amount = $cartItem->getAmount() + $payload['amount'];
                $total = $cartItem->getTotal() + $payload['total'];
                if ($amount > $cartItem->getProductItem()->getAmount()){
                    return $this->handleView($this->view(
                        ['error' => 'The product is quantity for this order has been exceeded'],
                        Response::HTTP_BAD_REQUEST
                    ));
                }
                $payload['amount'] = $amount;
                $payload['total'] = $total;
            }

            $form = $this->createForm(CartItemType::class, $cartItem);
            $form->submit($payload);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->cartRepository->add($cartItem);

                return $this->handleView($this->view(
                    ['success' => 'Insert cart item successfully'],
                    Response::HTTP_CREATED
                ));
            }

            $errorsMessage = $this->handleDataOutput->getFormErrorMessage($form);

            return $this->handleView($this->view(['error' => $errorsMessage], Response::HTTP_BAD_REQUEST));
        } catch (\Exception $e) {
            //Need to add log the error message
        }

        return $this->handleView($this->view([
            'error' => 'Something went wrong! Please contact support.'
        ],Response::HTTP_INTERNAL_SERVER_ERROR));
    }

    /**
     * @Rest\Put("/users/carts/{id}")
     * @IsGranted("ROLE_USER")
     * @param Request $request
     * @return Response
     */
    public function updateCart(Request $request, int $id): Response
    {
        try {
            $payload = json_decode($request->getContent(), true);
            $cartItem = $this->cartRepository->find($id);

            if ($cartItem) {
                $form = $this->createForm(CartItemType::class, $cartItem);
                $payload['productItem'] = $cartItem->getProductItem()->getId();
                $form->submit($payload);
                if ($form->isSubmitted() && $form->isValid()) {
                    $this->cartRepository->add($cartItem);

                    return $this->handleView($this->view(
                        ['success' => 'Update cart item successfully'],
                        Response::HTTP_NO_CONTENT
                    ));
                }

                $errorsMessage = $this->handleDataOutput->getFormErrorMessage($form);
            } else {
                $errorsMessage = ['id' => 'No item in cart was found with this id.'];
            }

            return $this->handleView($this->view(['error' => $errorsMessage], Response::HTTP_BAD_REQUEST));
        } catch (\Exception $e) {
            //Need to add log the error message
        }

        return $this->handleView($this->view([
            'error' => 'Something went wrong! Please contact support.'
        ],Response::HTTP_INTERNAL_SERVER_ERROR));
    }

    /**
     * @Rest\Delete("/users/carts/{id}")
     * @IsGranted("ROLE_USER")
     * @param int $id
     * @return Response
     */
    public function deleteCart(int $id): Response
    {
        try {
            $cartItem = $this->cartRepository->findOneBy(['id' => $id]);
            if ($cartItem) {
                $this->cartRepository->remove($cartItem);

                return $this->handleView($this->view([], Response::HTTP_NO_CONTENT));
            }

            return $this->handleView($this->view(
                ['error' => 'No item in cart was found with this id.'],
                Response::HTTP_NOT_FOUND
            ));
        } catch (\Exception $e) {
            //Need to add log the error message
        }

        return $this->handleView($this->view([
            'error' => 'Something went wrong! Please contact support.'
        ],Response::HTTP_INTERNAL_SERVER_ERROR));
    }

    /**
     * @Rest\Get("/users/carts/count")
     * @return Response
     */
    public function countCartItems(): Response
    {
        try {
            $countCartItems = $this->cartRepository->countCartItems($this->userLoginInfo->getId());

            return $this->handleView($this->view($countCartItems[0], Response::HTTP_OK));
        } catch (\Exception $e) {
            //Need to add log the error message
        }

        return $this->handleView($this->view(
            ['error' => 'Something went wrong! Please contact support.'],
            Response::HTTP_INTERNAL_SERVER_ERROR
        ));
    }

    /**
     * @param Cart $cart
     * @return array
     */
    private function dataTransferCartItemObject(Cart $cart): array
    {
        $formattedCart = [];

        $formattedCart['id'] = $cart->getId();
        $formattedCart['idProduct'] = $cart->getProductItem()->getProduct()->getId();
        $formattedCart['idProductItem'] = $cart->getProductItem()->getId();
        $formattedCart['name'] = $cart->getProductItem()->getProduct()->getName();
        $formattedCart['color'] = $cart->getProductItem()->getProduct()->getColor();
        $formattedCart['size'] = $cart->getProductItem()->getSize()->getName();
        $formattedCart['amount'] = $cart->getAmount();
        $formattedCart['price'] = $cart->getTotal();
        $formattedCart['unitPrice'] = $cart->getProductItem()->getProduct()->getPrice();
        $formattedCart['images'] = $cart->getProductItem()->getProduct()->getImages();
        $formattedCart['totalAmount'] = $cart->getProductItem()->getAmount();

        return $formattedCart;
    }
}
