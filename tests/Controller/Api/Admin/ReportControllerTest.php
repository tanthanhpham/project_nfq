<?php

namespace App\Tests\Controller\Api\Admin;

use App\DataFixtures\OrderFixtures;
use App\DataFixtures\UserFixtures;
use App\Tests\Controller\BaseWebTestCase;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportControllerTest extends BaseWebTestCase
{
    use ReloadDatabaseTrait;


    public function testGetReport()
    {
        $order = new OrderFixtures();
        $this->loadFixture($order);

        $payload = [
            'fromDate' => null,
            'toDate' => null
        ];

        $this->client->request(
            Request::METHOD_POST,
            '/api/admin/reports',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token)
            ],
            json_encode($payload)
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(1, $data['totalProduct']);
        $this->assertSame('600000', $data['totalRevenue']);
        $this->assertSame(1, $data['totalOrder']);
        $this->assertCount(4, $data['order']);
    }

    public function testGetDataForChart()
    {
        $order = new OrderFixtures();
        $this->loadFixture($order);

        $this->client->request(
            Request::METHOD_GET,
            '/api/admin/reports/chart',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token)
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }
}
