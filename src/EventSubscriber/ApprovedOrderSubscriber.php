<?php

namespace App\EventSubscriber;

use App\Event\OrderEvent;

class ApprovedOrderSubscriber extends AbstractOrderSubscriber
{
    public function onSendOrder(OrderEvent $event)
    {
        $order = $event->getOrder();

        $params = [
            "order" => $order
        ];

        $this->mailerService->send(
            'Confirm order information',
            'phamtanthanh.it@gmail.com',
            $order->getRecipientEmail(),
            OrderEvent::TEMPLATE_APPROVE,
            $params
        );
    }
}
