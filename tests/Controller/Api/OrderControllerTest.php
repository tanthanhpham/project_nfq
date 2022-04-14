<?php

namespace App\Tests\Controller\Api;

use App\DataFixtures\CategoryFixtures;
use App\DataFixtures\OrderFixtures;
use App\Tests\Controller\BaseWebTestCase;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderControllerTest extends BaseWebTestCase
{
    use ReloadDatabaseTrait;

    public function testGetOrders()
    {
        $orderFixtures = new OrderFixtures();
        $this->loadFixture($orderFixtures);

        $this->client->request(
            Request::METHOD_GET,
            '/api/users/orders',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token),
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data['data']);
        $this->assertCount(1, $data['data']);
        $order = $data['data'][0];
        $this->assertSame(1, $data['total']);
        $this->assertSame('User name', $order['recipientName']);
    }

    public function testFilterOrder()
    {
        $orderFixtures = new OrderFixtures();
        $this->loadFixture($orderFixtures);

        $this->client->request(
            Request::METHOD_GET,
            '/api/users/orders/filter?status=4',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token),
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data['data']);
        $this->assertCount(1, $data['data']);
        $order = $data['data'][0];
        $this->assertSame(1, $data['total']);
        $this->assertSame('User name', $order['recipientName']);
    }

    public function testGetOrder()
    {
        $orderFixtures = new OrderFixtures();
        $this->loadFixture($orderFixtures);

        $this->client->request(
            Request::METHOD_GET,
            '/api/users/orders/1',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token),
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertSame('User name', $data['recipientName']);
    }

    public function testUpdateStatusOrder()
    {
        $orderFixtures = new OrderFixtures();
        $this->loadFixture($orderFixtures);

        $payload = [
            'status' => 2
        ];

        $this->client->request(
            Request::METHOD_PUT,
            '/api/admin/orders/1',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token),
            ],
            json_encode($payload)
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testBuyAgain()
    {
        $orderFixtures = new OrderFixtures();
        $this->loadFixture($orderFixtures);

        $this->client->request(
            Request::METHOD_GET,
            '/api/users/orders/1/buyAgain',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token),
            ]
        );
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }
}
