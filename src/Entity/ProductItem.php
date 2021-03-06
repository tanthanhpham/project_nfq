<?php

namespace App\Entity;

use App\Repository\ProductItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=ProductItemRepository::class)
 */
class ProductItem
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"showProductItems"})
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     * @Groups({"showProductItems"})
     */
    private $amount;

    /**
     * @ORM\ManyToOne(targetEntity=Product::class, inversedBy="productItems", cascade={"persist"})
     */
    private $product;

    /**
     * @ORM\ManyToOne(targetEntity=Size::class, inversedBy="productItems", cascade={"persist"})
     * @Groups({"showProductItems"})
     */
    private $size;

    /**
     * @ORM\OneToMany(targetEntity=OrderDetail::class, mappedBy="productItem", cascade={"persist"})
     */
    private $orderDetails;

    /**
     * @ORM\OneToMany(targetEntity=Cart::class, mappedBy="productItem", cascade={"persist"})
     */
    private $carts;

    public function __construct()
    {
        $this->orderDetails = new ArrayCollection();
        $this->carts = new ArrayCollection();
    }

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

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getSize(): ?Size
    {
        return $this->size;
    }

    public function setSize(?Size $size): self
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @return Collection<int, OrderDetail>
     */
    public function getOrderDetails(): Collection
    {
        return $this->orderDetails;
    }

    public function addOrderDetail(OrderDetail $orderDetail): self
    {
        if (!$this->orderDetails->contains($orderDetail)) {
            $this->orderDetails[] = $orderDetail;
            $orderDetail->setProductItem($this);
        }

        return $this;
    }

    public function removeOrderDetail(OrderDetail $orderDetail): self
    {
        if ($this->orderDetails->removeElement($orderDetail)) {
            // set the owning side to null (unless already changed)
            if ($orderDetail->getProductItem() === $this) {
                $orderDetail->setProductItem(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Cart>
     */
    public function getCarts(): Collection
    {
        return $this->carts;
    }

    public function addCart(Cart $cart): self
    {
        if (!$this->carts->contains($cart)) {
            $this->carts[] = $cart;
            $cart->setProductItem($this);
        }

        return $this;
    }

    public function removeCart(Cart $cart): self
    {
        if ($this->carts->removeElement($cart)) {
            // set the owning side to null (unless already changed)
            if ($cart->getProductItem() === $this) {
                $cart->setProductItem(null);
            }
        }

        return $this;
    }
}
