<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OrderRepository::class)
 * @ORM\Table(name="`order`")
 */
class Order
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $status;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="orders", cascade={"persist"})
     */
    private $customer;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $recipientName;

    /**
     * @ORM\Column(type="text")
     */
    private $addressDelivery;

    /**
     * @ORM\Column(type="string", length=11)
     */
    private $recipientPhone;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $recipientEmail;

    /**
     * @ORM\Column(type="bigint")
     */
    private $totalPrice;

    /**
     * @ORM\Column(type="integer")
     */
    private $totalQuantity;

    /**
     * @ORM\OneToMany(targetEntity=OrderDetail::class, mappedBy="purchaseOrder", orphanRemoval=true, cascade={"persist"})
     */
    private $orderItems;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $shippingCost;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $reasonCancel;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $subjectCancel;

    /**
     * @ORM\OneToMany(targetEntity=Payment::class, mappedBy="�order")
     */
    private $paymentToken;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $paymentMethod;

    public function __construct(User $user)
    {
        $this->createdAt = new \DateTime('now');
        $this->setDate();
        $this->setCustomer($user);
        $this->setStatus('1');
        $this->orderItems = new ArrayCollection();
        $this->paymentToken = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(): self
    {
        $this->date = new \DateTime('now');

        return $this;
    }

    public function getCreateAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreateAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdateAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdateAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(?User $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function getRecipientName(): ?string
    {
        return $this->recipientName;
    }

    public function setRecipientName(string $recipientName): self
    {
        $this->recipientName = $recipientName;

        return $this;
    }

    public function getAddressDelivery(): ?string
    {
        return $this->addressDelivery;
    }

    public function setAddressDelivery(string $addressDelivery): self
    {
        $this->addressDelivery = $addressDelivery;

        return $this;
    }

    public function getSubjectCancel(): ?string
    {
        return $this->subjectCancel;
    }

    public function setSubjectCancel(string $subjectCancel): self
    {
        $this->subjectCancel = $subjectCancel;

        return $this;
    }

    public function getReasonCancel(): ?string
    {
        return $this->reasonCancel;
    }

    public function setReasonCancel(string $reasonCancel): self
    {
        $this->reasonCancel = $reasonCancel;

        return $this;
    }

    public function getRecipientPhone(): ?string
    {
        return $this->recipientPhone;
    }

    public function setRecipientPhone(string $recipientPhone): self
    {
        $this->recipientPhone = $recipientPhone;

        return $this;
    }

    public function getRecipientEmail(): ?string
    {
        return $this->recipientEmail;
    }

    public function setRecipientEmail(string $recipientEmail): self
    {
        $this->recipientEmail = $recipientEmail;

        return $this;
    }


    public function getTotalPrice(): ?string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(string $totalPrice): self
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getTotalQuantity(): ?int
    {
        return $this->totalQuantity;
    }

    public function setTotalQuantity(int $totalQuantity): self
    {
        $this->totalQuantity = $totalQuantity;

        return $this;
    }

    /**
     * @return Collection<int, OrderDetail>
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderDetail $orderItem): self
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems[] = $orderItem;
            $orderItem->setPurchaseOrder($this);
        }

        return $this;
    }

    public function removeOrderItem(OrderDetail $orderItem): self
    {
        if ($this->orderItems->removeElement($orderItem)) {
            // set the owning side to null (unless already changed)
            if ($orderItem->getPurchaseOrder() === $this) {
                $orderItem->setPurchaseOrder(null);
            }
        }

        return $this;
    }

    public function getShippingCost(): ?int
    {
        return $this->shippingCost;
    }

    public function setShippingCost(?int $shippingCost): self
    {
        $this->shippingCost = $shippingCost;

        return $this;
    }

    public function getToken(): ?Payment
    {
        return $this->token;
    }

    public function setToken(Payment $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return Collection<int, Payment>
     */
    public function getPaymentToken(): Collection
    {
        return $this->paymentToken;
    }

    public function addPaymentToken(Payment $paymentToken): self
    {
        if (!$this->paymentToken->contains($paymentToken)) {
            $this->paymentToken[] = $paymentToken;
            $paymentToken->setorder($this);
        }

        return $this;
    }

    public function removePaymentToken(Payment $paymentToken): self
    {
        if ($this->paymentToken->removeElement($paymentToken)) {
            // set the owning side to null (unless already changed)
            if ($paymentToken->getorder() === $this) {
                $paymentToken->setorder(null);
            }
        }

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }
}
