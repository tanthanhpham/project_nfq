<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Order;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class UserEvent.
 */
class OrderEvent extends Event
{
    const TEMPLATE_APPROVE = "email/invoice.html.twig";
    const TEMPLATE_REJECT = "email/reject.html.twig";
    const TEMPLATE_CANCEL = "email/cancel.html.twig";

    public const STATUS_PENDING = 1;
    public const STATUS_APPROVED = 2;
    public const STATUS_CANCELED = 3;
    public const STATUS_COMPLETED = 4;

    public const APPROVED = 'order.approved';
    public const CANCELED = 'order.canceled';
    public const COMPLETED = 'order.completed';

    /**
     * @var Order
     */
    public $order;

    /**
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * @return Order
     */
    public function getOrder(): Order
    {
        return $this->order;
    }
}
