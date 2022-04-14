<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Order;
use App\Entity\OrderDetail;
use App\Entity\Product;
use App\Entity\ProductItem;
use App\Entity\Size;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\Query\AST\Functions\MaxFunction;
use Doctrine\Persistence\ObjectManager;

class OrderFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setName('User name');
        $user->setEmail('user@gmail.com');
        $user->setPhone('0888888888');
        $user->setAddress('Address user');
        $user->setRoles(['ROLE_ADMIN']);
        $user->setUpdatedAt(new \DateTime());
        $user->setPassword('password');

        $category = new Category();
        $category->setName('Category name');
        $category->setImage('/image');

        $size = new Size();
        $size->setName('S');

        $product = new Product();
        $product->setCategory($category);
        $product->setName('Product name 1');
        $product->setDescription('Product description 1');
        $product->setPrice(300000);
        $product->setMaterial('Product Material');
        $product->setUpdateAt(new \DateTime());
        $product->setColor('Red');
        $product->setImages(['a:2:{i:0;s:52:"127.0.0.1/uploads/images/test-Copy-62485b4938ed5.jpg";i:1;s:47:"127.0.0.1/uploads/images/test-62485b493e505.jpg";}']);

        $productItem = new ProductItem();
        $productItem->setSize($size);
        $productItem->setAmount(10);
        $productItem->setProduct($product);
        $orderDetail = new OrderDetail();

        $orderDetail->setProductItem($productItem);
        $orderDetail->setAmount(2);
        $orderDetail->setTotal(600000);

        $order = new Order($user);
        $order->setRecipientName('User name');
        $order->setRecipientEmail('user@gmail.com');
        $order->setStatus(4);
        $order->setDate(new \DateTime());
        $order->setCreateAt(new \DateTime());
        $order->setUpdateAt(new \DateTime());
        $order->setRecipientPhone('0981063207');
        $order->setAddressDelivery('Address');
        $order->setTotalQuantity('2');
        $order->setTotalPrice(600000);
        $order->setShippingCost(30000);
        $order->addOrderItem($orderDetail);

        $manager->persist($order);
        $manager->flush();
    }
}
