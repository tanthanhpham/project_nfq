<?php

namespace App\EventSubscriber;

use App\Event\OrderEvent;
use App\Service\MailerService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderSubscriber implements EventSubscriberInterface
{
    /**
     * @var MailerService
     */
    protected $mailerService;

    public function __construct(MailerService $mailerService)
    {
        $this->mailerService = $mailerService;
    }

    public function onSendOrder(OrderEvent $event)
    {
        $order = $event->getOrder();
        $status = $order->getStatus();
        $template = OrderEvent::TEMPLATE_APPROVE;
        $subject = 'Order Confirmation #Order' .  $order->getId();

        $params = [
            "order" => $order
        ];

        if ($status == OrderEvent::STATUS_CANCELED) {
            $template = OrderEvent::TEMPLATE_CANCEL;
            $subject = 'Order Cancellation Confirmation #Order' .  $order->getId();
        }

        if ($status == OrderEvent::STATUS_COMPLETED){
            $template = OrderEvent::TEMPLATE_REJECT;
            $subject = 'Order Cancellation Notice #Order' .  $order->getId();
        }

        $this->mailerService->send(
            $subject,
            'phamtanthanh.it@gmail.com',
            $order->getRecipientEmail(),
            $template,
            $params
        );
    }
    public static function getSubscribedEvents()
    {
        return[
            OrderEvent::class => [
                ['onSendOrder', 1]
            ]
        ];
    }
}
