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
     * @Rest\Post("/users/orders")
     * @param Request $request
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function insertOrder(Request $request): Response
    {
        $order = new Order($this->userLoginInfo);
        $form = $this->createForm(OrderType::class, $order);
        $requestData = $request->request->all();
        $form->submit($requestData);

        $totalPrice = 0;
        $totalAmount = 0;
        if ($form->isSubmitted()) {
            $cartItemsData = $this->userLoginInfo->getCarts();
            foreach ($cartItemsData as $cartItemData){
                $productItem = $cartItemData->getProductItem();
                $amount = intval($cartItemData->getAmount());

                if($amount > $productItem->getAmount()) {
                    return $this->handleView($this->view(['error' => 'Quantity is not enough.'], Response::HTTP_BAD_REQUEST));
                }

                $price = intval($cartItemData->getTotal()) * $amount;
                $totalPrice += intval($price);
                $totalAmount += $amount;
                $orderDetail = new OrderDetail();
                $orderDetail->setAmount($amount);
                $orderDetail->setTotal($price);

                $productItem->setAmount($productItem->getAmount() - $amount);
                $this->productItemRepository->add($productItem);
                $orderDetail->setProductItem($productItem);

                dd($orderDetail);
                $order->addOrderItem($orderDetail);
                $this->cartRepository->remove($cartItemData);
            }

            $this->purchaseOrderRepository->add($order);
            return $this->handleView($this->view($transferPurchaseOrder, Response::HTTP_CREATED));
        }

        return $this->handleView($this->view($form->getErrors(), Response::HTTP_BAD_REQUEST));
    }

    /**
     * @Rest\Delete("/users/orders/{id}")
     * @param PurchaseOrder $purchaseOrder
     * @return Response
     */
    public function cancelPurchaseOrderAction(PurchaseOrder $purchaseOrder): Response
    {
        try {
            $status = $purchaseOrder->getStatus();
            if ($status == '1') {
                $purchaseOrder->setStatus('3');
                $purchaseOrder->setUpdateAt(new \DateTime());
                $purchaseOrder->setDeleteAt(new \DateTime());

                $items = $purchaseOrder->getOrderItems();
                foreach ($items as $item) {
                    $productItem = $item->getProductItem();
                    $productItem->setAmount($productItem->getAmount() + $item->getAmount());
                    $productItem->setUpdateAt(new \DateTime());

                    $this->productItemRepository->add($productItem);
                }

                $this->purchaseOrderRepository->add($purchaseOrder);

                return $this->handleView($this->view($purchaseOrder, Response::HTTP_OK));
            }

            return $this->handleView($this->view(['error' => 'This order is approved. So, your request is failed.'], Response::HTTP_BAD_REQUEST));
        }
        catch (\Exception $e) {
            //write to log
        }

        return $this->handleView($this->view([
            'error' => 'Something went wrong! Please contact support.'
        ],Response::HTTP_INTERNAL_SERVER_ERROR));
    }
}