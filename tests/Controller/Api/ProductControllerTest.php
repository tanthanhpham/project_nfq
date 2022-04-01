<?php

namespace App\Tests\Controller\Api;

use App\DataFixtures\ProductFixtures;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use App\Tests\Controller\BaseWebTestCase;

class ProductControllerTest extends BaseWebTestCase
{
    use ReloadDatabaseTrait;

    public function testGetProducts()
    {
        $productFixtures = new ProductFixtures();
        $this->loadFixture($productFixtures);

        $this->client->request(
            Request::METHOD_GET,
            '/api/products',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $product = $data[0];
        $this->assertSame('Product name', $product['name']);
    }

    public function testFilter()
    {
        $productFixtures = new ProductFixtures();
        $this->loadFixture($productFixtures);

        $payload = [
            'category' => 1,
            'minPrice' => 20000,
            'maxPrice' => 50000
        ];
        $this->client->request(
            Request::METHOD_POST,
            '/api/products/filter',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode($payload)
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $product = $data[0];
        $this->assertSame('Product name', $product['name']);
    }
}
