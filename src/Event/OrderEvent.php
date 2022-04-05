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