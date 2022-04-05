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

        $this->mailerService->send(
            'Hello',
            'tanthanh.pham@nfq.asia',
            'phamtanthanh.it@gmail.com',
            'Hello world'
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
