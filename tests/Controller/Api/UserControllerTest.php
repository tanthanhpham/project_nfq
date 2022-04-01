<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use App\DataFixtures\AppDataFixtures;
use App\DataFixtures\CategoryFixtures;
use App\DataFixtures\UserFixtures;
use App\Tests\Controller\BaseWebTestCase;
use Hautelook\AliceBundle\PhpUnit\ReloadDatabaseTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Bundle\FrameworkBundle\Client as Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group functional
 * @covers
 *
 * @internal
 */
class UserControllerTest extends BaseWebTestCase
{
    use ReloadDatabaseTrait;

    public function testGetOneUser()
    {
        $user = new UserFixtures();
        $this->loadFixture($user);

        $this->client->request(
            Request::METHOD_GET,
            'api/users/1',
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
        $user = $data[0];
        $this->assertSame('User name', $user['name']);
    }

    public function testGetAllUser()
    {
        $user = new UserFixtures();
        $this->loadFixture($user);

        $this->client->request(
            Request::METHOD_GET,
            'api/users',
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
        $user = $data[0];
        $this->assertSame('User name', $user['name']);
    }

    public function testGetUserByEmail()
    {
        $user = new UserFixtures();
        $this->loadFixture($user);

        $payload = [
            'email' => 'user@gmail.com',
            'password' => 'password'
        ];
        $this->client->request(
            Request::METHOD_POST,
            'api/users/email',
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
        $user = $data[0];
        $this->assertSame('User name', $user['name']);
    }

    public function testRegister()
    {
        $payload = [
            'name' => 'User name',
            'email' => 'user@gmail.com',
            'phone' => '088888888',
            'address' => 'User address',
            'password' => 'password',
            'image' => '/image.png'
        ];

        $this->client->request(
            Request::METHOD_POST,
            'api/register',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode($payload)
        );

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $user = $data[0];
        $this->assertSame('User name', $user['name']);
    }
}
