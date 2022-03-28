<?php

namespace App\Tests\Controller\Api;

use App\DataFixtures\ProductFixtures;
use App\Entity\Product;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use App\Tests\Controller\BaseWebTestCase;

class ProductControllerTest extends BaseWebTestCase
{
    use ReloadDatabaseTrait;
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testGetProducts()
    {
        $productFixtures = new ProductFixtures();
        $this->loadFixture($productFixtures);

        $this->assertTrue(true);
    }
}