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
    public const STATUS_PENDING = 1;
    public const STATUS_APPROVED = 2;
    public const STATUS_CANCELED = 3;
    public const STATUS_COMPLETED = 4;

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
    public function getOrders(): Response
    {
        $userId = $this->userLoginInfo->getId();
        $orders = $this->purchaseOrderRepository->findBy(['deletedAt' => null, 'customer' => $userId], ['createdAt' => 'DESC']);
        $transferOrders = array_map('self::dataTransferObject', $orders);

        return $this->handleView($this->view($transferOrders, Response::HTTP_OK));
    }

    /**
     * @Rest\Get("/users/orders/{id}")
     * @param Order $purchaseOrder
     * @return Response
     */
    public function getOrder(Order $purchaseOrder): Response
    {
        $transferPurchaseOrder = self::dataTransferDetailOrderObject($purchaseOrder);

        return $this->handleView($this->view($transferPurchaseOrder, Response::HTTP_OK));
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

    private function dataTransferObject(Order $purchaseOrder): array
    {
        $formattedPurchaseOrder = [];
        $formattedPurchaseOrder['id'] = $purchaseOrder->getId();
        $formattedPurchaseOrder['recipientName'] = $purchaseOrder->getRecipientName();
        $formattedPurchaseOrder['recipientEmail'] = $purchaseOrder->getRecipientEmail();
        $formattedPurchaseOrder['recipientPhone'] = $purchaseOrder->getRecipientPhone();
        $formattedPurchaseOrder['addressDelivery'] = $purchaseOrder->getAddressDelivery();
        switch (intval($purchaseOrder->getStatus())) {
            case self::STATUS_PENDING:
                $formattedPurchaseOrder['status'] = 'Pending';
                break;
            case self::STATUS_APPROVED:
                $formattedPurchaseOrder['status'] = 'Approved';
                break;
            case self::STATUS_CANCELED:
                $formattedPurchaseOrder['status'] = 'Canceled';
                break;
            case self::STATUS_COMPLETED:
                $formattedPurchaseOrder['status'] = 'Completed';
                break;
        }
        $formattedPurchaseOrder['amount'] = $purchaseOrder->getTotalQuantity();
        $formattedPurchaseOrder['totalPrice'] = $purchaseOrder->getTotalPrice();

        return $formattedPurchaseOrder;
    }

    private function dataTransferDetailOrderObject(Order $purchaseOrder): array
    {
        $formattedPurchaseOrder = [];
        $formattedPurchaseOrder['id'] = $purchaseOrder->getId();
        $formattedPurchaseOrder['recipientName'] = $purchaseOrder->getRecipientName();
        $formattedPurchaseOrder['recipientEmail'] = $purchaseOrder->getRecipientEmail();
        $formattedPurchaseOrder['recipientPhone'] = $purchaseOrder->getRecipientPhone();
        $formattedPurchaseOrder['addressDelivery'] = $purchaseOrder->getAddressDelivery();
        $formattedPurchaseOrder['status'] = $purchaseOrder->getStatus();
        $formattedPurchaseOrder['amount'] = $purchaseOrder->getTotalQuantity();
        $formattedPurchaseOrder['totalPrice'] = $purchaseOrder->getTotalPrice();

        $cartItems = $purchaseOrder->getOrderItems();
        foreach ($cartItems as $cartItem) {
            $formattedPurchaseOrder['items'][] =  self::dataTransferItemObject($cartItem);
        }

        return $formattedPurchaseOrder;
    }

    private function dataTransferItemObject(OrderDetail $orderDetail): array
    {
        $item = [];
        $productItem = $orderDetail->getProductItem();
        $item['id'] = $orderDetail->getId();
        $item['name'] = $productItem->getProduct()->getName();
        $item['size'] = $productItem->getSize()->getName();
        $item['amount'] = $orderDetail->getAmount();
        $item['unitPrice'] = $productItem->getProduct()->getPrice();
        $item['price'] = $orderDetail->getTotal();

        return $item;
    }
}