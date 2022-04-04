<?php

namespace App\Tests\Controller\Api\Admin;

use App\DataFixtures\CategoryFixtures;
use App\Tests\Controller\BaseWebTestCase;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CategoryControllerTest extends BaseWebTestCase
{
    use ReloadDatabaseTrait;

    public function testGetCategories()
    {
        $categoryFixtures = new CategoryFixtures();
        $this->loadFixture($categoryFixtures);

        $this->client->request(
            Request::METHOD_GET,
            'api/categories',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $category = $data[0];
        $this->assertSame('Product category', $category['name']);
    }
}
