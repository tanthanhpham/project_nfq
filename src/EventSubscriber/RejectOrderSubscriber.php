<?php

namespace App\EventSubscriber;

use App\Event\OrderEvent;

class RejectOrderSubscriber extends AbstractOrderSubscriber
{
    public function onSendOrder(OrderEvent $event)
    {
        $order = $event->getOrder();

        $params = [
            "order" => $order
        ];

        $this->mailerService->send(
            'Reject order',
            'phamtanthanh.it@gmail.com',
            $order->getRecipientEmail(),
            OrderEvent::TEMPLATE_CANCEL,
            $params
        );
    }
}
