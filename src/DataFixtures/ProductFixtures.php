<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        $category = new Category();
        $category->setName('Category name');
        $category->setImage('/category_image');

        $product = new Product();
        $product->setName('Product name');
        $product->setPrice(30000);
        $product->setImage('/product_image');
        $product->setMaterial('Product material');
        $product->setDescription('Product description');
        $product->setImage('product_image.png');
        $product->setColor('Red');
        $product->setCreateAt(new \DateTime());
        $product->setUpdateAt(new \DateTime());
        $product->setDeletedAt(new \DateTime());
        $product->setCategory($category);
        $manager->persist($product);

        $product2 = new Product();
        $product2->setName('Product 2 name');
        $product2->setPrice(10000);
        $product2->setImage('/product2_image');
        $product2->setMaterial('Product 2 material');
        $product2->setDescription('Product 2 description');
        $product2->setImage('product2_image.png');
        $product2->setColor('Red');
        $product2->setCreateAt(new \DateTime());
        $product2->setUpdateAt(new \DateTime());
        $product2->setDeletedAt(new \DateTime());
        $product2->setCategory($category);
        $manager->persist($product2);
        $manager->flush();
    }
}
