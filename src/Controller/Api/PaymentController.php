<?php

namespace App\Controller\Api;

use App\Controller\Api\BaseController;
use App\Entity\Order;
use App\Event\OrderEvent;
use App\Form\OrderType;
use PayPal\Api\Amount;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;

class PaymentController extends BaseController
{
    /**
     * @Rest\Post ("/users/orders/payments")
     */
    public function payment(Request $request)
    {
        $apiContext = $this->paymentService->getApiContext();

        $approveUrl = $this->domain . '/api/users/orders/payments/approve';
        $cancelUrl = $this->domain . '/api/users/orders/payments/cancel';

        $order = new Order($this->userLoginInfo);
        $form = $this->createForm(OrderType::class, $order);
        $requestData = $request->request->all();

        $arrResult = $this->orderService->addOrder($order, $form, $requestData);
        if (isset($arrResult['error'])) {
            return $this->handleView($this->view($arrResult, Response::HTTP_BAD_REQUEST));
        }

        $currency = 'USD';
        $amountPayable = $order->getShippingCost() + $order->getTotalPrice();
        $paymentResult = $this->paymentService->createPayment($order, $apiContext, $approveUrl, $cancelUrl);

        if (isset($payment['error'])) {
            return $this->handleView($this->view($paymentResult, Response::HTTP_BAD_REQUEST));
        }
        $payment = $paymentResult;

        $paymentEntity = new \App\Entity\Payment();
        $paymentEntity->setToken($payment->getToken());
        $paymentEntity->setCurrencyCode($currency);
        $paymentEntity->setAmount($amountPayable);
        $paymentEntity->setStatus($payment->getState());
        $paymentEntity->setTransactionId($payment->getId());
        $paymentEntity->setOrder($order);
        $order->setStatus(self::STATUS_PENDING_PAYMENT);
        $this->paymentRepository->add($paymentEntity);

        return $this->handleView($this->view(['url' => $payment->getApprovalLink()], Response::HTTP_OK));
    }

    /**
     * @Rest\Get ("/users/orders/payments/approve")
     */
    public function approve(Request $request)
    {
        $apiContext = $this->paymentService->getApiContext();

        $paymentId = $request->get('paymentId');
        $payerId = $request->get('PayerID');

        $payment = Payment::get($paymentId, $apiContext);
        $execution = new PaymentExecution();
        $execution->setPayerId($payerId);

        try {
            $payment->execute($execution, $apiContext);

            try {
                $paymentEntity = $this->paymentRepository->findOneBy([
                    'transactionId' => $paymentId
                ]);

                $paymentEntity->setStatus($payment->getState());
                $order = $paymentEntity->getOrder();

                $event = new OrderEvent($order);
                $this->eventDispatcher->dispatch($event);

                $order->setStatus(self::STATUS_APPROVED);
                $this->orderRepository->add($order);

                return $this->redirect('https://nfq-unlock.info/user-profile');
            } catch (\Exception $e) {
                return $this->handleView($this->view(['error' => 'Something is wrong'], Response::HTTP_INTERNAL_SERVER_ERROR));
            }

        } catch (\Exception $e) {
            return $this->handleView($this->view(['error' => 'Something is wrong, please try later'], Response::HTTP_BAD_REQUEST));
        }
    }

    /**
     * @Rest\Get ("/users/orders/payments/cancel")
     */
    public function cancel(Request $request): Response
    {
        $tokenPaypal = $request->get('token');

        $payment = $this->paymentRepository->findOneBy([
            'token' => $tokenPaypal
        ]);

        $payment->getOrder()->setStatus(self::STATUS_PENDING_PAYMENT);

        return $this->redirect('https://nfq-unlock.info/user-profile');
    }

    /**
     * @Rest\Put ("/users/orders/payments/{id}")
     */
    public function rePayment(int $id)
    {
        $apiContext = $this->paymentService->getApiContext();

        $order = $this->orderRepository->find($id);
        $approveUrl = $this->domain . '/api/users/orders/payments/approve';
        $cancelUrl = $this->domain . '/api/users/orders/payments/cancel';

        $currency = 'USD';
        $amountPayable = $order->getShippingCost() + $order->getTotalPrice();
        $payment = $this->paymentService->createPayment($order, $apiContext, $approveUrl, $cancelUrl);

        $paymentEntity = new \App\Entity\Payment();
        $paymentEntity->setToken($payment->getToken());
        $paymentEntity->setCurrencyCode($currency);
        $paymentEntity->setAmount($amountPayable);
        $paymentEntity->setStatus($payment->getState());
        $paymentEntity->setTransactionId($payment->getId());
        $paymentEntity->setOrder($order);
        $order->setStatus(self::STATUS_PENDING_PAYMENT);
        $this->paymentRepository->add($paymentEntity);

        return $this->handleView($this->view(['url' => $payment->getApprovalLink()], Response::HTTP_OK));
    }
}