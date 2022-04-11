<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductItem;
use App\Entity\Size;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $size = new Size();
        $size->setName('S');

        $category = new Category();
        $category->setName('Category name');
        $category->setImage('/category_image');

        $productItem = new ProductItem();
        $productItem->setSize($size);
        $productItem->setAmount(20);

        $product = new Product();
        $product->setName('Product name');
        $product->setPrice(30000);
        $product->setImages(['/product_image']);
        $product->setMaterial('Product material');
        $product->setDescription('Product description');
        $product->setImages(['product_image.png']);
        $product->setColor('Red');
        $product->setCreateAt(new \DateTime());
        $product->setUpdateAt(new \DateTime());
        $product->setCategory($category);
        $product->addProductItem($productItem);
        $manager->persist($product);

        $product2 = new Product();
        $product2->setName('Product 2 name');
        $product2->setPrice(10000);
        $product2->setImages(['/product2_image']);
        $product2->setMaterial('Product 2 material');
        $product2->setDescription('Product 2 description');
        $product2->setImages(['product2_image.png']);
        $product2->setColor('Red');
        $product2->setCreateAt(new \DateTime());
        $product2->setUpdateAt(new \DateTime());
        $product2->setDeletedAt(new \DateTime());
        $product2->setCategory($category);
        $manager->persist($product2);
        $manager->flush();
    }
}
