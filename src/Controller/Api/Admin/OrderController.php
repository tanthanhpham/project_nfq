<?php

namespace App\Controller\Api\Admin;

use App\Controller\Api\BaseController;
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
class OrderController extends BaseController
{
    /**
     * @Rest\Get("/admin/orders")
     * @return Response
     */
    public function getOrders(Request $request): Response
    {
        $orders = $this->orderRepository->findByConditions(['deletedAt' => null], ['status' => 'ASC']);
        $orders['data'] = array_map('self::dataTransferObject', $orders['data']);

        return $this->handleView($this->view($orders, Response::HTTP_OK));
    }

    /**
     * @Rest\Get("/admin/orders/{id}")
     * @return Response
     */
    public function getOrder(int $id): Response
    {
        $order = $this->orderRepository->findOneBy(['deletedAt' => null, 'id' => $id]);
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
            }
        }

        $this->orderRepository->add($purchaseOrder);

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

        $filePath = 'https://api.nfq-unlock.info' . $pdf->generateBinaryPDF($html);

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
        $formattedPurchaseOrder['paymentMethod'] = $purchaseOrder->getPaymentMethod();
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
        $item['image'] = self::formatImages($orderDetail->getProductItem()->getProduct()->getImages());
        $item['color'] = $orderDetail->getProductItem()->getProduct()->getColor();

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
