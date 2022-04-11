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
use App\Service\PdfService;
use Dompdf\Dompdf;
use Dompdf\Options;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Validator\Constraints\Date;

/**
 * @IsGranted("ROLE_ADMIN")
 */
class OrderController extends AbstractFOSRestController
{
    public const STATUS_PENDING = 1;
    public const STATUS_APPROVED = 2;
    public const STATUS_CANCELED = 3;
    public const STATUS_COMPLETED = 4;
    public const ORDER_PAGE_LIMIT = 10;
    public const ORDER_PAGE_PAGE = 1;

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
    ) {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
        $this->userLoginInfo = $userLogin->getUserLoginInfo();
        $this->productItemRepository = $productItemRepository;
        $this->cartRepository = $cartRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @Rest\Get("/admin/orders")
     * @return Response
     */
    public function getOrders(Request $request): Response
    {
        $limit = $request->get('limit', self::ORDER_PAGE_LIMIT);
        $page = $request->get('page', self::ORDER_PAGE_PAGE);

        $offset = $limit * ($page - 1);
        $orders = $this->purchaseOrderRepository->findByConditions(['deletedAt' => null], ['status' => 'ASC'], $limit, $offset);
        $orders['data'] = array_map('self::dataTransferObject', $orders['data']);

        return $this->handleView($this->view($orders, Response::HTTP_OK));
    }

    /**
     * @Rest\Get("/admin/orders/{id}")
     * @return Response
     */
    public function getOrder(int $id): Response
    {
        $order = $this->purchaseOrderRepository->findOneBy(['deletedAt' => null, 'id' => $id]);
        $order = self::dataTransferDetailOrderObject($order);

        return $this->handleView($this->view($order, Response::HTTP_OK));
    }

    /**
     * @Rest\Put("/admin/orders/{id}")
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

        if ($status != $purchaseOrder->getStatus()) {
            $purchaseOrder->setStatus($status);
            $purchaseOrder->setUpdateAt(new \DateTime('now'));
            if ($status == self::STATUS_CANCELED) {
                $purchaseOrder->setSubjectCancel('admin');
                $purchaseOrder->setReasonCancel($requestData['reasonCancel']);

                $event = new OrderEvent($purchaseOrder);
                $this->eventDispatcher->dispatch($event);
            }
        }

        $this->purchaseOrderRepository->add($purchaseOrder);

        $event = new OrderEvent($purchaseOrder);
        $this->eventDispatcher->dispatch($event);

        $purchaseOrder = self::dataTransferObject($purchaseOrder);

        return $this->handleView($this->view($purchaseOrder, Response::HTTP_OK));
    }

    /**
     * @Rest\Get("/admin/orders/{id}/export")
     * @param Order $order
     * @param PdfService $pdf
     * @return Response
     */
    public function generatePdfInvoice(Order $order, PdfService $pdf): Response
    {
        $html = $this->render('export/pdf.html.twig', ['order' => $order]);
        $filePath = 'http://127.0.0.1' . $pdf->generateBinaryPDF($html);

        return $this->handleView($this->view($filePath, Response::HTTP_OK));
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
