<?php

namespace App\Controller\Api;

use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\OrderDetail;
use App\Entity\ProductItem;
use App\Event\OrderEvent;
use App\Form\CartItemType;
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
use function Symfony\Component\String\s;

/**
 * @IsGranted("ROLE_USER")
 */
class OrderController extends BaseController
{
    /**
     * @Rest\Get("/users/orders")
     * @return Response
     */
    public function getOrders(Request $request): Response
    {
        $userId = $this->userLoginInfo->getId();
        $limit = $request->get('limit', self::ITEM_PAGE_LIMIT);
        $page = $request->get('page', self::ITEM_PAGE_NUMBER);

        $offset = $limit * ($page - 1);
        $orders = $this->orderRepository->findByConditions(['deletedAt' => null, 'customer' => $userId], ['createdAt' => 'DESC'], $limit, $offset);
        $orders['data'] = array_map('self::dataTransferObject', $orders['data']);

        return $this->handleView($this->view($orders, Response::HTTP_OK));
    }

    /**
     * @Rest\Get("/users/orders/filter")
     * @param Request $request
     * @param Response
     * @return void
     */
    public function filterOrder(Request $request): Response
    {
        $userId = $this->userLoginInfo->getId();
        $limit = $request->get('limit', self::ITEM_PAGE_LIMIT);
        $page = $request->get('page', self::ITEM_PAGE_NUMBER);
        $status = $request->get('status');

        $offset = $limit * ($page - 1);

        $orders = $this->orderRepository->findByConditions(['deletedAt' => null, 'customer' => $userId, 'status' => $status]
            , ['createdAt' => 'DESC'], $limit, $offset);
        $orders['data'] = array_map('self::dataTransferObject', $orders['data']);

        return $this->handleView($this->view($orders, Response::HTTP_OK));
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
            if (count($cartItemsData) == 0) {
                return $this->handleView($this->view(['error' => 'Your cart is empty.'], Response::HTTP_BAD_REQUEST));
            }
            foreach ($cartItemsData as $cartItemData) {
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
                $order->addOrderItem($orderDetail);
//                $this->cartRepository->remove($cartItemData);
            }
            $order->setTotalPrice($totalPrice);
            $order->setTotalQuantity($totalQuantity);
            $order->setUpdateAt(new \DateTime('now'));
            $this->orderRepository->add($order);

            $event = new OrderEvent($order);
            $this->eventDispatcher->dispatch($event);

            return $this->handleView($this->view(['message' => 'Add order successfully'], Response::HTTP_CREATED));
        }

        return $this->handleView($this->view($form->getErrors(), Response::HTTP_BAD_REQUEST));
    }

    /**
     * @Rest\Put("/users/orders/{id}")
     * @param Order $purchaseOrder
     * @param Request $request
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateStatusOrder(Order $purchaseOrder, Request $request): Response
    {
        $requestData = json_decode($request->getContent(), true);
        $status = $requestData['status'];

        if ($purchaseOrder->getStatus() == self::STATUS_APPROVED) {
            $purchaseOrder->setStatus($status);
            $purchaseOrder->setUpdateAt(new \DateTime('now'));
            if ($status == self::STATUS_CANCELED) {
                $purchaseOrder->setSubjectCancel('user');
                $purchaseOrder->setReasonCancel($requestData['reasonCancel']);
            }
            $this->orderRepository->add($purchaseOrder);

            $event = new OrderEvent($purchaseOrder);
            $this->eventDispatcher->dispatch($event);

            return $this->handleView($this->view(['message' => 'Cancel order successfully'], Response::HTTP_OK));
        }

        return $this->handleView($this->view(['message' => 'Can not cancel order'], Response::HTTP_OK));
    }

    /**
     * @Rest\get("/users/orders/{id}/buyAgain")
     * @return Response
     */
    public function buyAgain(int $id): Response
    {
        try {
            $order = $this->orderRepository->findOneBy(['id' => $id, 'customer' => $this->userLoginInfo->getId()]);
            $orderDetail = $order->getOrderItems();
            $countItemsAddCart = 0;
            foreach ($orderDetail as $item) {
                $recordCart = [
                    'productItem' => $item->getProductItem()->getId(),
                    'amount' => $item->getAmount(),
                    'total' => $item->getTotal(),
                ];
                $check = $this->cartService->addCart($recordCart);
                if ($check)
                    $countItemsAddCart += 1;
            }

            if ($countItemsAddCart == 0) {
                return $this->handleView($this->view(['error' => 'Can not add product to cart'], Response::HTTP_BAD_REQUEST));
            }

            return $this->handleView($this->view(['message' => 'Add ' . $countItemsAddCart . ' items to cart'], Response::HTTP_OK));

        } catch (\Exception $e) {
        }

        return $this->handleView($this->view(
            ['error' => 'Something went wrong! Please contact support.'],
            Response::HTTP_INTERNAL_SERVER_ERROR
        ));
    }

    private function dataTransferObject(Order $purchaseOrder): array
    {
        $formattedPurchaseOrder = [];
        $formattedPurchaseOrder['id'] = $purchaseOrder->getId();
        $formattedPurchaseOrder['recipientName'] = $purchaseOrder->getRecipientName();
        $formattedPurchaseOrder['recipientEmail'] = $purchaseOrder->getRecipientEmail();
        $formattedPurchaseOrder['recipientPhone'] = $purchaseOrder->getRecipientPhone();
        $formattedPurchaseOrder['addressDelivery'] = $purchaseOrder->getAddressDelivery();
        $formattedPurchaseOrder['orderDate'] = $purchaseOrder->getCreateAt()->format('Y-m-d H:i');
        $formattedPurchaseOrder['paymentMethod'] = $purchaseOrder->getPaymentMethod();

        switch (intval($purchaseOrder->getStatus())) {
            case self::STATUS_APPROVED:
                $formattedPurchaseOrder['status'] = 'Approved';
                break;
            case self::STATUS_DELIVERY:
                $formattedPurchaseOrder['status'] = 'Delivery';
                break;
            case self::STATUS_CANCELED:
                $formattedPurchaseOrder['status'] = 'Canceled';
                break;
            case self::STATUS_COMPLETED:
                $formattedPurchaseOrder['status'] = 'Completed';
                break;
            case self::STATUS_PENDING_PAYMENT:
                $formattedPurchaseOrder['status'] = 'Pending';
                break;
        }
        $formattedPurchaseOrder['amount'] = $purchaseOrder->getTotalQuantity();
        $formattedPurchaseOrder['totalPrice'] = $purchaseOrder->getTotalPrice() + $purchaseOrder->getShippingCost();
        $cartItems = $purchaseOrder->getOrderItems();

        foreach ($cartItems as $cartItem) {
            $formattedPurchaseOrder['firstItem'][] =  self::dataTransferItemObject($cartItem);
            break;
        }

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
        $formattedPurchaseOrder['totalPrice'] = $purchaseOrder->getTotalPrice() + $purchaseOrder->getShippingCost();
        $formattedPurchaseOrder['orderDate'] = $purchaseOrder->getCreateAt()->format('Y-m-d H:i:s');
        $formattedPurchaseOrder['shippingCost'] = $purchaseOrder->getShippingCost();

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
        $item['idProduct'] = $orderDetail->getProductItem()->getProduct()->getId();
        $item['id'] = $orderDetail->getId();
        $item['name'] = $productItem->getProduct()->getName();
        $item['size'] = $productItem->getSize()->getName();
        $item['amount'] = $orderDetail->getAmount();
        $item['unitPrice'] = $productItem->getProduct()->getPrice();
        $item['price'] = $orderDetail->getTotal();
        $item['image'] = $orderDetail->getProductItem()->getProduct()->getImages();
        $item['color'] = $orderDetail->getProductItem()->getProduct()->getColor();

        return $item;
    }
}
