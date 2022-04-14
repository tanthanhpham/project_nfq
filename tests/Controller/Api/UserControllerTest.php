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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group functional
 * @covers
 * @internal
 */
class UserControllerTest extends BaseWebTestCase
{
    use ReloadDatabaseTrait;

//    public function testRegister()
//    {
//        $payload = [
//            'name' => 'User name',
//            'email' => 'user@gmail.com',
//            'phone' => '0981063207',
//            'address' => 'User address',
//            'password' => 'password',
//        ];
//
//        $image = new UploadedFile(
//            __DIR__. '/../../../fixtures/test.png',
//            'test.png',
//            'png',
//            null,
//            true
//        );
//
//        $this->client->request(
//            Request::METHOD_POST,
//            'api/register', $payload,
//            [
//                'image' => $image
//            ],
//            [
//                'HTTP_ACCEPT' => 'application/x-www-form-urlencoded',
//                'CONTENT_TYPE' => 'multipart/form-data',
//            ]
//        );
//
//        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
//    }

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
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token)
            ]
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertSame('User name', $data['name']);
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

        $this->assertSame('User name', $data['name']);
    }

    public function testGetUserByPhone()
    {
        $user = new UserFixtures();
        $this->loadFixture($user);

        $payload = [
            'phone' => '0888888888',
        ];
        $this->client->request(
            Request::METHOD_POST,
            'api/users/phone',
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

        $this->assertSame('User name', $data['name']);
    }

    public function testUpdateUser()
    {
        $user = new UserFixtures();
        $this->loadFixture($user);

        $payload = [
            "phone" => "0888888888",
            "address" => "User address"

        ];
        $this->client->request(
            Request::METHOD_PUT,
            'api/users/1',
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

//    public function testUpdateAvatar()
//    {
//        $user = new UserFixtures();
//        $this->loadFixture($user);
//
//        $image = new UploadedFile(
//            __DIR__. '/../../../fixtures/test.png',
//            'test.png',
//            'png',
//            null,
//            true
//        );
//
//        $this->client->request(
//            Request::METHOD_POST,
//            'api/users/1/image',
//            [],
//            [
//                'image' => $image
//            ],
//            [
//                'HTTP_ACCEPT' => 'application/x-www-form-urlencoded',
//                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', self::$token),
//            ]
//        );
//
//        $this->assertEquals(Response::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
//    }
}
