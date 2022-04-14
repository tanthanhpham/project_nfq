<?php

namespace App\Tests\Controller\Api\Admin;

use App\DataFixtures\CategoryFixtures;
use App\DataFixtures\ProductFixtures;
use App\DataFixtures\UserFixtures;
use App\Tests\Controller\BaseWebTestCase;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductControllerTest extends BaseWebTestCase
{
    use ReloadDatabaseTrait;

    public function testGetProducts()
    {
        $productFixtures = new ProductFixtures();
        $this->loadFixture($productFixtures);

        $user = new UserFixtures();
        $this->loadFixture($user);

        $this->client->request(
            Request::METHOD_GET,
            '/api/admin/products',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token)
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $product = $data['data']['0'];
        $this->assertSame('Product name', $product['name']);
    }

    public function testGetProduct()
    {
        $productFixtures = new ProductFixtures();
        $this->loadFixture($productFixtures);

        $user = new UserFixtures();
        $this->loadFixture($user);

        $this->client->request(
            Request::METHOD_GET,
            '/api/admin/products/1',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token)
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertSame('Product name', $data['name']);
    }

//    public function testInsertProduct()
//    {
//        $user = new UserFixtures();
//        $this->loadFixture($user);
//
//        $categoryFixtures = new CategoryFixtures();
//        $this->loadFixture($categoryFixtures);
//
//        $images[] = new UploadedFile(
//            __DIR__. '/../../../../fixtures/test.png',
//            'test.png',
//            'png',
//            null,
//            true
//        );
//
//        $payload = [
//            'name' => 'Product name',
//            'price' => 100000,
//            'description' => 'Product description',
//            'material' => 'Product material',
//            'color' => 'Product color',
//            'category' => 1,
//        ];
//
//        $this->client->request(
//            Request::METHOD_POST,
//            'api/admin/products', $payload,
//            [
//                'images' => $images
//            ],
//            [
//                'HTTP_ACCEPT' => 'application/json',
//                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token)
//            ]
//        );
//        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
//    }

    public function testUpdateProduct()
    {
        $productFixtures = new ProductFixtures();
        $this->loadFixture($productFixtures);

        $user = new UserFixtures();
        $this->loadFixture($user);

        $payload = [
            'name' => 'Product name',
            'price' => 100000,
            'description' => 'Product description',
            'material' => 'Product material',
            'color' => 'Product color',
            'category' => 1,
        ];
        $this->client->request(
            Request::METHOD_POST,
            'api/admin/products/1', $payload,
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token)
            ]
        );

        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteProduct()
    {
        $productFixtures = new ProductFixtures();
        $this->loadFixture($productFixtures);

        $user = new UserFixtures();
        $this->loadFixture($user);

        $this->client->request(
            Request::METHOD_DELETE,
            'api/admin/products/1',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token)
            ]
        );

        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    public function testGetProductItems()
    {

        $productFixtures = new ProductFixtures();
        $this->loadFixture($productFixtures);

        $user = new UserFixtures();
        $this->loadFixture($user);

        $this->client->request(
            Request::METHOD_GET,
            'api/admin/products/1/productItems',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token)
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $productItem = $data['0'];
        $this->assertSame(20,$productItem['amount']);
    }

    public function testUpdateProductItems()
    {

        $productFixtures = new ProductFixtures();
        $this->loadFixture($productFixtures);

        $payload[] = [
            "size" => 1,
            "amount" => 100,
        ];

        $user = new UserFixtures();
        $this->loadFixture($user);

        $this->client->request(
            Request::METHOD_PUT,
            'api/admin/products/1/productItem',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token)
            ],
            json_encode($payload)
        );

        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }
}
