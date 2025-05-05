<?php

namespace Tests\Unit;

use App\Entity\User;
use App\Repository\DynamoDBUserRepository;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Exception\AwsException;
use PHPUnit\Framework\TestCase;
use Mockery;

class DynamoDBUserRepositoryTest extends TestCase
{
    private $client;
    private $repository;
    private $tableName = 'test_users';

    protected function setUp(): void
    {
        $this->client = Mockery::mock(DynamoDbClient::class);
        $this->repository = new DynamoDBUserRepository($this->client, $this->tableName);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testSaveUserSuccess()
    {
        $user = new User(
            'test123',
            'テストユーザー',
            25,
            'male',
            ['スポーツ', '音楽'],
            '東京'
        );

        $this->client->shouldReceive('putItem')
            ->once()
            ->with([
                'TableName' => $this->tableName,
                'Item' => [
                    'id' => ['S' => 'test123'],
                    'name' => ['S' => 'テストユーザー'],
                    'age' => ['N' => '25'],
                    'gender' => ['S' => 'male'],
                    'interests' => ['S' => json_encode(['スポーツ', '音楽'])],
                    'location' => ['S' => '東京']
                ]
            ])
            ->andReturnNull();

        $this->repository->save($user);
        $this->assertTrue(true); // 例外が発生しなければ成功
    }

    public function testSaveUserFailure()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DynamoDB error');

        $user = new User(
            'test123',
            'テストユーザー',
            25,
            'male',
            ['スポーツ', '音楽'],
            '東京'
        );

        $this->client->shouldReceive('putItem')
            ->once()
            ->andThrow(new AwsException('DynamoDB error', new \Aws\Command('putItem')));

        $this->repository->save($user);
    }

    public function testFindUserSuccess()
    {
        $userId = 'test123';

        $this->client->shouldReceive('getItem')
            ->once()
            ->with([
                'TableName' => $this->tableName,
                'Key' => [
                    'id' => ['S' => $userId]
                ]
            ])
            ->andReturn([
                'Item' => [
                    'id' => ['S' => 'test123'],
                    'name' => ['S' => 'テストユーザー'],
                    'age' => ['N' => '25'],
                    'gender' => ['S' => 'male'],
                    'interests' => ['S' => json_encode(['スポーツ', '音楽'])],
                    'location' => ['S' => '東京']
                ]
            ]);

        $user = $this->repository->find($userId);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test123', $user->getId());
        $this->assertEquals('テストユーザー', $user->getName());
        $this->assertEquals(25, $user->getAge());
        $this->assertEquals('male', $user->getGender());
        $this->assertEquals(['スポーツ', '音楽'], $user->getInterests());
        $this->assertEquals('東京', $user->getLocation());
    }

    public function testFindUserNotFound()
    {
        $userId = 'nonexistent';

        $this->client->shouldReceive('getItem')
            ->once()
            ->with([
                'TableName' => $this->tableName,
                'Key' => [
                    'id' => ['S' => $userId]
                ]
            ])
            ->andReturn([]);

        $user = $this->repository->find($userId);

        $this->assertNull($user);
    }

    public function testFindByNameAndLocationSuccess()
    {
        $name = 'テストユーザー';
        $location = '東京';

        $this->client->shouldReceive('scan')
            ->once()
            ->with([
                'TableName' => $this->tableName,
                'FilterExpression' => '#name = :name AND #location = :location',
                'ExpressionAttributeNames' => [
                    '#name' => 'name',
                    '#location' => 'location'
                ],
                'ExpressionAttributeValues' => [
                    ':name' => ['S' => $name],
                    ':location' => ['S' => $location]
                ]
            ])
            ->andReturn([
                'Items' => [[
                    'id' => ['S' => 'test123'],
                    'name' => ['S' => 'テストユーザー'],
                    'age' => ['N' => '25'],
                    'gender' => ['S' => 'male'],
                    'interests' => ['S' => json_encode(['スポーツ', '音楽'])],
                    'location' => ['S' => '東京']
                ]]
            ]);

        $user = $this->repository->findByNameAndLocation($name, $location);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test123', $user->getId());
        $this->assertEquals('テストユーザー', $user->getName());
        $this->assertEquals(25, $user->getAge());
        $this->assertEquals('male', $user->getGender());
        $this->assertEquals(['スポーツ', '音楽'], $user->getInterests());
        $this->assertEquals('東京', $user->getLocation());
    }

    public function testFindByNameAndLocationNotFound()
    {
        $name = '存在しないユーザー';
        $location = '存在しない場所';

        $this->client->shouldReceive('scan')
            ->once()
            ->with([
                'TableName' => $this->tableName,
                'FilterExpression' => '#name = :name AND #location = :location',
                'ExpressionAttributeNames' => [
                    '#name' => 'name',
                    '#location' => 'location'
                ],
                'ExpressionAttributeValues' => [
                    ':name' => ['S' => $name],
                    ':location' => ['S' => $location]
                ]
            ])
            ->andReturn(['Items' => []]);

        $user = $this->repository->findByNameAndLocation($name, $location);

        $this->assertNull($user);
    }

    public function testFindAllUsers()
    {
        $this->client->shouldReceive('scan')
            ->once()
            ->with([
                'TableName' => $this->tableName
            ])
            ->andReturn([
                'Items' => [
                    [
                        'id' => ['S' => 'test1'],
                        'name' => ['S' => 'ユーザー1'],
                        'age' => ['N' => '25'],
                        'gender' => ['S' => 'male'],
                        'interests' => ['S' => json_encode(['スポーツ'])],
                        'location' => ['S' => '東京']
                    ],
                    [
                        'id' => ['S' => 'test2'],
                        'name' => ['S' => 'ユーザー2'],
                        'age' => ['N' => '30'],
                        'gender' => ['S' => 'female'],
                        'interests' => ['S' => json_encode(['音楽'])],
                        'location' => ['S' => '大阪']
                    ]
                ]
            ]);

        $users = $this->repository->findAll();

        $this->assertCount(2, $users);
        $this->assertInstanceOf(User::class, $users[0]);
        $this->assertInstanceOf(User::class, $users[1]);
        $this->assertEquals('ユーザー1', $users[0]->getName());
        $this->assertEquals('ユーザー2', $users[1]->getName());
    }
}
