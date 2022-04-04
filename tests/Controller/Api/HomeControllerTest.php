<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\DataFixtures\AppDataFixtures;
use App\DataFixtures\CategoryFixtures;
use App\DataFixtures\ProductFixtures;
use App\DataFixtures\UserFixtures;
use App\Tests\Controller\BaseWebTestCase;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Bundle\FrameworkBundle\Client as Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group functional
 * @covers
 *
 * @internal
 */
class HomeControllerTest extends BaseWebTestCase
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

    public function testGetProduct()
    {
        $productFixtures = new ProductFixtures();
        $this->loadFixture($productFixtures);

        $this->client->request(
            Request::METHOD_GET,
            '/api/products/1',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertSame('Product name', $data['name']);
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
