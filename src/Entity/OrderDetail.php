<?php

namespace App\Entity;

use App\Repository\OrderDetailRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OrderDetailRepository::class)
 */
class OrderDetail
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $amount;

    /**
     * @ORM\Column(type="integer")
     */
    private $total;


    /**
     * @ORM\ManyToOne(targetEntity=ProductItem::class, inversedBy="orderDetails", cascade={"persist"})
     */
    private $productItem;

    /**
     * @ORM\ManyToOne(targetEntity=Order::class, inversedBy="orderItems", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $purchaseOrder;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getTotal(): ?int
    {
        return $this->total;
    }

    public function setTotal(int $total): self
    {
        $this->total = $total;

        return $this;
    }

    public function getProductItem(): ?ProductItem
    {
        return $this->productItem;
    }

    public function setProductItem(?ProductItem $productItem): self
    {
        $this->productItem = $productItem;

        return $this;
    }

    public function getPurchaseOrder(): ?Order
    {
        return $this->purchaseOrder;
    }

    public function setPurchaseOrder(?Order $purchaseOrder): self
    {
        $this->purchaseOrder = $purchaseOrder;

        return $this;
    }
}
