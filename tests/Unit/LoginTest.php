<?php

namespace Tests\Unit;

use App\Entity\User;
use App\Repository\DynamoDBUserRepository;
use PHPUnit\Framework\TestCase;
use Aws\DynamoDb\DynamoDbClient;
use Aws\MockHandler;
use Aws\Result;

class LoginTest extends TestCase
{
    private $mockHandler;
    private $dynamoDbClient;
    private $userRepository;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $this->dynamoDbClient = new DynamoDbClient([
            'region' => 'ap-northeast-1',
            'version' => 'latest',
            'credentials' => [
                'key' => 'dummy',
                'secret' => 'dummy'
            ],
            'endpoint' => 'http://dynamodb-local:8000',
            'handler' => $this->mockHandler
        ]);
        $this->userRepository = new DynamoDBUserRepository($this->dynamoDbClient, 'users');
    }

    public function testFindUserByNameAndLocation()
    {
        // モックのレスポンスを設定
        $this->mockHandler->append(new Result([
            'Items' => [
                [
                    'id' => ['S' => 'test-user-id'],
                    'name' => ['S' => '山田太郎'],
                    'age' => ['N' => '30'],
                    'gender' => ['S' => '男性'],
                    'interests' => ['SS' => ['旅行', '読書']],
                    'location' => ['S' => '東京']
                ]
            ]
        ]));

        $user = $this->userRepository->findByNameAndLocation('山田太郎', '東京');

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test-user-id', $user->getId());
        $this->assertEquals('山田太郎', $user->getName());
        $this->assertEquals(30, $user->getAge());
        $this->assertEquals('男性', $user->getGender());
        $this->assertEquals(['旅行', '読書'], $user->getInterests());
        $this->assertEquals('東京', $user->getLocation());
    }

    public function testFindUserByNameAndLocationNotFound()
    {
        // 空のレスポンスを設定
        $this->mockHandler->append(new Result([
            'Items' => []
        ]));

        $user = $this->userRepository->findByNameAndLocation('存在しないユーザー', '東京');

        $this->assertNull($user);
    }

    public function testFindUserById()
    {
        // モックのレスポンスを設定
        $this->mockHandler->append(new Result([
            'Item' => [
                'id' => ['S' => 'test-user-id'],
                'name' => ['S' => '山田太郎'],
                'age' => ['N' => '30'],
                'gender' => ['S' => '男性'],
                'interests' => ['SS' => ['旅行', '読書']],
                'location' => ['S' => '東京']
            ]
        ]));

        $user = $this->userRepository->findById('test-user-id');

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test-user-id', $user->getId());
        $this->assertEquals('山田太郎', $user->getName());
        $this->assertEquals(30, $user->getAge());
        $this->assertEquals('男性', $user->getGender());
        $this->assertEquals(['旅行', '読書'], $user->getInterests());
        $this->assertEquals('東京', $user->getLocation());
    }

    public function testFindUserByIdNotFound()
    {
        // 空のレスポンスを設定
        $this->mockHandler->append(new Result([
            'Item' => null
        ]));

        $user = $this->userRepository->findById('存在しないID');

        $this->assertNull($user);
    }
}
