<?php

namespace App\Controller\Api\Admin;

use App\Entity\Order;
use App\Entity\OrderDetail;
use App\Entity\ProductItem;
use App\Event\OrderEvent;
use App\Form\OrderType;
use App\Repository\CartRepository;
use App\Repository\OrderRepository;
use App\Repository\ProductItemRepository;
use App\Service\GetUserInfo;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_ADMIN")
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
    private $eventDispatcher;

    public function __construct(
        OrderRepository $purchaseOrderRepository,
        GetUserInfo $userLogin,
        ProductItemRepository $productItemRepository,
        CartRepository $cartRepository,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->userLoginInfo = $userLogin->getUserLoginInfo();
        $this->productItemRepository = $productItemRepository;
        $this->cartRepository = $cartRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @Rest\Put("/admin/orders/{id}")
     * @param Order $purchaseOrder
     * @param Request $request
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateStatusOrderAction(Order $purchaseOrder, Request $request): Response
    {
        $status = $request->get('status');

        if($status != $purchaseOrder->getStatus()) {
            $purchaseOrder->setStatus($status);
            $purchaseOrder->setUpdatedAt();
        }

        $this->purchaseOrderRepository->add($purchaseOrder);

        $purchaseOrder = self::dataTransferObject($purchaseOrder);

        return $this->handleView($this->view($purchaseOrder, Response::HTTP_OK));
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