<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Entity\OrderDetail;
use App\Entity\ProductItem;
use App\Form\OrderType;
use App\Repository\CartRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductItemRepository;
use App\Service\GetUserInfo;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER")
 */
class OrderController extends AbstractFOSRestController
{
    private $purchaseOrderRepository;
    private $productItemRepository;
    private $userLoginInfo;
    private $cartRepository;

    public function __construct(
        OrderRepository $purchaseOrderRepository,
        GetUserInfo $userLogin,
        ProductItemRepository $productItemRepository,
        CartRepository $cartRepository
    )
    {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->userLoginInfo = $userLogin->getUserLoginInfo();
        $this->productItemRepository = $productItemRepository;
        $this->cartRepository = $cartRepository;
    }

    /**
     * @Rest\Get("/users/orders")
     * @return Response
     */
    public function getOrdersAction(): Response
    {
        $userId = $this->userLoginInfo->getId();
        $orders = $this->purchaseOrderRepository->findBy(['deletedAt' => null, 'customer' => $userId], ['createdAt' => 'DESC']);

        return $this->handleView($this->view($orders, Response::HTTP_OK));
    }

    /**
     * @Rest\Post("/users/orders")
     * @param Request $request
     * @return Response
     */
    public function insertOrder(Request $request): Response
    {
        $order = new Order($this->userLoginInfo);
        $form = $this->createForm(OrderType::class, $order);
        $requestData = $request->request->all();
        $form->submit($requestData);
        $totalPrice = 0;
        $totalQuantity = 0;
        if ($form->isSubmitted()) {
            $cartItemsData = $this->userLoginInfo->getCarts();
            foreach ($cartItemsData as $cartItemData){
                $productItem = $cartItemData->getProductItem();
                $quantity = $cartItemData->getAmount();

                if ($quantity > $productItem->getAmount()) {
                    return $this->handleView($this->view(['error' => 'Quantity is not enough.'], Response::HTTP_BAD_REQUEST));
                }

                $totalPrice += $cartItemData->getTotal();
                $totalQuantity += $cartItemData->getAmount();

                $orderDetail = new OrderDetail();
                $orderDetail->setAmount($cartItemData->getAmount());
                $orderDetail->setTotal($cartItemData->getTotal());

                $productItem->setAmount($productItem->getAmount() - $cartItemData->getAmount());
                $this->productItemRepository->add($productItem);
                $orderDetail->setProductItem($productItem);

                $order->setDate(new \DateTime());
                $order->setUpdatedAt(new \DateTime());
                $order->addOrderItem($orderDetail);

                $this->cartRepository->remove($cartItemData);
            }
            $order->setTotalPrice($totalPrice);
            $order->setTotalQuantity($totalQuantity);

            $this->purchaseOrderRepository->add($order);
            return $this->handleView($this->view(['message' => 'Add order successfully'], Response::HTTP_CREATED));
        }

        return $this->handleView($this->view($form->getErrors(), Response::HTTP_BAD_REQUEST));
    }
}