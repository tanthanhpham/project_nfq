<?php

namespace App\DataFixtures;

use App\Entity\Cart;
use App\Entity\Category;
use App\Entity\Gallery;
use App\Entity\Product;
use App\Entity\ProductItem;
use App\Entity\Size;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CartFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setImage('/image');
        $user->setEmail('user@gmail.com');
        $user->setPassword('password');
        $user->setAddress('User address');
        $user->setPhone('0981063207');
        $user->setCreatedAt();
        $user->setUpdatedAt(new \DateTime());
        $user->setRoles(['ROLE_USER']);

        $category = new Category();
        $category->setName('Category name');
        $category->setImage('/image');

        $size = new Size();
        $size->setName('M');

        $firstProduct = new Product();
        $firstProduct->setCategory($category);
        $firstProduct->setName('Product name 1');
        $firstProduct->setDescription('Product description 1');
        $firstProduct->setPrice(300000);
        $firstProduct->setMaterial('Product Material');
        $firstProduct->setUpdateAt(new \DateTime());
        $firstProduct->setColor('Red');
        $firstProduct->setCreateAt();
        $firstProduct->setImages(['a:2:{i:0;s:52:"127.0.0.1/uploads/images/test-Copy-62485b4938ed5.jpg";i:1;s:47:"127.0.0.1/uploads/images/test-62485b493e505.jpg";}']);

        $firstProductItem = new ProductItem();
        $firstProductItem->setSize($size);
        $firstProductItem->setAmount(10);
        $firstProductItem->setProduct($firstProduct);

        $firstCartItem = new Cart();
        $firstCartItem->setProductItem($firstProductItem);
        $firstCartItem->setUser($user);
        $firstCartItem->setAmount(1);
        $firstCartItem->setTotal($firstProduct->getPrice());

        $manager->persist($firstCartItem);

        $manager->flush();
    }
}