<?php

namespace App\Tests\Controller\Api\Admin;

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
            Request::METHOD_PUT,
            'api/admin/products/1', $payload,
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token)
            ]
        );

        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }
}
