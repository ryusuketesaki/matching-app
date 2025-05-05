<?php

namespace Tests\Repository;

use App\Entity\User;
use App\Repository\DynamoDBUserRepository;
use Aws\DynamoDb\DynamoDbClient;
use PHPUnit\Framework\TestCase;
use Aws\MockHandler;
use Aws\Result;

class DynamoDBUserRepositoryTest extends TestCase
{
    private DynamoDBUserRepository $repository;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $client = new DynamoDbClient([
            'region' => 'us-east-1',
            'version' => 'latest',
            'handler' => $this->mockHandler
        ]);

        $this->repository = new DynamoDBUserRepository($client, 'test-users');
    }

    public function testSave()
    {
        $user = new User(
            'test-id',
            'テストユーザー',
            25,
            'male',
            ['プログラミング', '読書'],
            '東京'
        );

        $this->mockHandler->append(new Result([
            'Attributes' => [
                'id' => ['S' => 'test-id'],
                'name' => ['S' => 'テストユーザー'],
                'age' => ['N' => '25'],
                'gender' => ['S' => 'male'],
                'interests' => ['SS' => ['プログラミング', '読書']],
                'location' => ['S' => '東京']
            ]
        ]));

        $this->repository->save($user);
        $this->assertTrue(true); // 例外が発生しなければ成功
    }

    public function testFindById()
    {
        $this->mockHandler->append(new Result([
            'Item' => [
                'id' => ['S' => 'test-id'],
                'name' => ['S' => 'テストユーザー'],
                'age' => ['N' => '25'],
                'gender' => ['S' => 'male'],
                'interests' => ['SS' => ['プログラミング', '読書']],
                'location' => ['S' => '東京']
            ]
        ]));

        $user = $this->repository->findById('test-id');

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test-id', $user->getId());
        $this->assertEquals('テストユーザー', $user->getName());
        $this->assertEquals(25, $user->getAge());
        $this->assertEquals('male', $user->getGender());
        $this->assertEquals(['プログラミング', '読書'], $user->getInterests());
        $this->assertEquals('東京', $user->getLocation());
    }

    public function testFindAll()
    {
        $this->mockHandler->append(new Result([
            'Items' => [
                [
                    'id' => ['S' => 'test-id-1'],
                    'name' => ['S' => 'テストユーザー1'],
                    'age' => ['N' => '25'],
                    'gender' => ['S' => 'male'],
                    'interests' => ['SS' => ['プログラミング', '読書']],
                    'location' => ['S' => '東京']
                ],
                [
                    'id' => ['S' => 'test-id-2'],
                    'name' => ['S' => 'テストユーザー2'],
                    'age' => ['N' => '30'],
                    'gender' => ['S' => 'female'],
                    'interests' => ['SS' => ['旅行', '料理']],
                    'location' => ['S' => '大阪']
                ]
            ]
        ]));

        $users = $this->repository->findAll();

        $this->assertCount(2, $users);
        $this->assertInstanceOf(User::class, $users[0]);
        $this->assertInstanceOf(User::class, $users[1]);
    }
}
