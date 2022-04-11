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

        $params = [
            "order" => $order
        ];

        if ($status == OrderEvent::STATUS_APPROVED) {
            $this->mailerService->send(
                'Confirm order information',
                'phamtanthanh.it@gmail.com',
                $order->getRecipientEmail(),
                OrderEvent::TEMPLATE_APPROVE,
                $params
            );
        }

        if ($status == OrderEvent::STATUS_CANCELED){
            if ($order->getSubjectCancel() == 'admin') {
                $this->mailerService->send(
                    'Reject order',
                    'phamtanthanh.it@gmail.com',
                    $order->getRecipientEmail(),
                    OrderEvent::TEMPLATE_REJECT,
                    $params
                );
            } else {
                $this->mailerService->send(
                    'Cancel order',
                    'phamtanthanh.it@gmail.com',
                    $order->getRecipientEmail(),
                    OrderEvent::TEMPLATE_CANCEL,
                    $params
                );
            }
        }
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
