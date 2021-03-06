<?php

namespace App\Service;

use App\Entity\Order;
use PayPal\Api\Amount;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class PaymentService
{

    /**
     * @var ContainerBagInterface
     */
    private $containerBag;

    /**
     * @param ContainerBagInterface $containerBag
     */
    public function __construct(ContainerBagInterface $containerBag)
    {
        $this->containerBag = $containerBag;
    }

    /**
     * @return ApiContext
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getApiContext()
    {
        $apiContext = new ApiContext(
            new OAuthTokenCredential($this->containerBag->get('app.paypal_client')
                , $this->containerBag->get('app.paypal_secret'))
        );

        $apiContext->setConfig([
            'mode' => true ? 'sandbox' : 'live'
        ]);

        return $apiContext;
    }

    /**
     * @param Order $order
     * @param ApiContext $apiContext
     * @param string $approveUrl
     * @param string $cancelUrl
     * @return Payment|string[]
     */
    public function createPayment(Order $order, ApiContext $apiContext, string $approveUrl, string $cancelUrl)
    {
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        $currency = 'USD';
        $amountPayable = $order->getShippingCost() + $order->getTotalPrice();
        $description = 'Paypal transaction';
        $invoiceNumber = uniqid();

        $amount = new Amount();
        $amount->setCurrency($currency)
            ->setTotal($amountPayable);

        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setDescription($description)
            ->setInvoiceNumber($invoiceNumber);

        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl($approveUrl)
            ->setCancelUrl($cancelUrl);

        $payment = new Payment();
        $payment->setIntent('sale')
            ->setPayer($payer)
            ->setTransactions([$transaction])
            ->setRedirectUrls($redirectUrls);

        try {
            $payment->create($apiContext);
        } catch (\Exception $e) {
            return ['error' => 'Unable to create link for payment'];
        }

        return $payment;
    }
}