<?php

namespace App\Entity;

use App\Repository\SizeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=SizeRepository::class)
 */
class Size
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"showProductItems"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     * @Groups({"showProductItems"})
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity=ProductItem::class, mappedBy="size", cascade={"persist"})
     */
    private $productItems;

    public function __construct()
    {
        $this->productItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, ProductItem>
     */
    public function getProductItems(): Collection
    {
        return $this->productItems;
    }

    public function addProductItem(ProductItem $productItem): self
    {
        if (!$this->productItems->contains($productItem)) {
            $this->productItems[] = $productItem;
            $productItem->setSize($this);
        }

        return $this;
    }

    public function removeProductItem(ProductItem $productItem): self
    {
        if ($this->productItems->removeElement($productItem)) {
            // set the owning side to null (unless already changed)
            if ($productItem->getSize() === $this) {
                $productItem->setSize(null);
            }
        }

        return $this;
    }
}
