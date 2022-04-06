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

        $params = [
            "order" => $order
        ];

        $this->mailerService->send(
            'Hello',
            'ttp.jp365@gmail.com',
            'phamtanthanh.it@gmail.com',
            OrderEvent::TEMPLATE_CONTACT,
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
