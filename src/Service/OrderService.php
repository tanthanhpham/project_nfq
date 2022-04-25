<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\OrderDetail;
use App\Entity\User;
use App\Form\CartItemType;
use App\Repository\CartRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;

class OrderService extends AbstractController
{
    public const METHOD_CAST = 'cast';
    public const METHOD_PAYPAL = 'paypal';

    /** @var User|null */
    private $userLoginInfo;

    private $orderRepository;

    private $productItemRepository;

    private $cartRepository;

    public function __construct(GetUserInfo $userLogin, OrderRepository $orderRepository, ProductItemRepository $productItemRepository, CartRepository $cartRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->productItemRepository = $productItemRepository;
        $this->userLoginInfo = $userLogin->getUserLoginInfo();
        $this->cartRepository = $cartRepository;
    }

    public function addOrder(Order $order, FormInterface $form, array $payload)
    {
        $form->submit($payload);
        $totalPrice = 0;
        $totalQuantity = 0;
        try {
            if ($form->isSubmitted()) {
                $cartItemsData = $this->userLoginInfo->getCarts();
                if (count($cartItemsData) == 0) {
                    return ['error' => 'Your cart is empty.'];
                }
                foreach ($cartItemsData as $cartItemData) {
                    $productItem = $cartItemData->getProductItem();
                    $quantity = $cartItemData->getAmount();

                    if ($quantity > $productItem->getAmount()) {
                        return ['error' => 'Quantity is not enough.'];
                    }

                    $totalPrice += $cartItemData->getTotal();
                    $totalQuantity += $cartItemData->getAmount();

                    $orderDetail = new OrderDetail();
                    $orderDetail->setAmount($cartItemData->getAmount());
                    $orderDetail->setTotal($cartItemData->getTotal());

                    $productItem->setAmount($productItem->getAmount() - $cartItemData->getAmount());
                    $this->productItemRepository->add($productItem);
                    $orderDetail->setProductItem($productItem);
                    $order->addOrderItem($orderDetail);
                    $this->cartRepository->remove($cartItemData);
                }
                $order->setTotalPrice($totalPrice);
                $order->setTotalQuantity($totalQuantity);
                $order->setUpdateAt(new \DateTime('now'));
                $this->orderRepository->add($order);
                return ['success' => 'Add order successfully.'];
            }
        } catch (\Exception $e) {
        }
    }
}