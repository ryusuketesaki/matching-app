<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Repository\DynamoDBUserRepository;
use Aws\DynamoDb\DynamoDbClient;
use Aws\MockHandler;
use Aws\Result;
use Aws\Exception\AwsException;

class UserListTest extends TestCase
{
    private $mockHandler;
    private $dynamoDbClient;
    private $userRepository;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $this->dynamoDbClient = new DynamoDbClient([
            'region' => 'us-east-1',
            'version' => 'latest',
            'handler' => $this->mockHandler,
            'credentials' => [
                'key' => 'dummy-key',
                'secret' => 'dummy-secret'
            ],
            'endpoint' => 'http://dynamodb-local:8000'
        ]);
        $this->userRepository = new DynamoDBUserRepository($this->dynamoDbClient, 'users');
    }

    public function testFindAllUsers()
    {
        $this->mockHandler->append(new Result([
            'Items' => [
                [
                    'id' => ['S' => '1'],
                    'name' => ['S' => 'User 1'],
                    'location' => ['S' => 'Tokyo'],
                    'age' => ['N' => '25'],
                    'gender' => ['S' => 'male'],
                    'interests' => ['SS' => ['Music', 'Sports']]
                ],
                [
                    'id' => ['S' => '2'],
                    'name' => ['S' => 'User 2'],
                    'location' => ['S' => 'Osaka'],
                    'age' => ['N' => '30'],
                    'gender' => ['S' => 'female'],
                    'interests' => ['SS' => ['Reading', 'Travel']]
                ]
            ],
            'LastEvaluatedKey' => null,
            'Count' => 2,
            'ScannedCount' => 2
        ]));

        $result = $this->userRepository->findAll();
        $users = $result['users'];

        $this->assertCount(2, $users);
        $this->assertEquals('1', $users[0]->getId());
        $this->assertEquals('User 1', $users[0]->getName());
        $this->assertEquals('Tokyo', $users[0]->getLocation());
        $this->assertEquals(25, $users[0]->getAge());
        $this->assertEquals(['Music', 'Sports'], $users[0]->getInterests());

        // ページネーション情報の検証
        $this->assertArrayHasKey('pagination', $result);
        $this->assertEquals(1, $result['pagination']['current_page']);
        $this->assertEquals(1, $result['pagination']['total_pages']);
        $this->assertEquals(2, $result['pagination']['total_users']);
        $this->assertEquals(20, $result['pagination']['per_page']);
    }

    public function testFindAllUsersWithFilters()
    {
        $this->mockHandler->append(new Result([
            'Items' => [
                [
                    'id' => ['S' => '1'],
                    'name' => ['S' => 'User 1'],
                    'location' => ['S' => 'Tokyo'],
                    'age' => ['N' => '25'],
                    'gender' => ['S' => 'male'],
                    'interests' => ['SS' => ['Music', 'Sports']]
                ]
            ],
            'LastEvaluatedKey' => null,
            'Count' => 1,
            'ScannedCount' => 2
        ]));

        $filters = [
            'location' => 'Tokyo',
            'age_min' => 20,
            'age_max' => 30,
            'gender' => 'male'
        ];

        $result = $this->userRepository->findAll($filters);
        $users = $result['users'];

        $this->assertCount(1, $users);
        $this->assertEquals('1', $users[0]->getId());
        $this->assertEquals('User 1', $users[0]->getName());
        $this->assertEquals('Tokyo', $users[0]->getLocation());
        $this->assertEquals(25, $users[0]->getAge());
        $this->assertEquals(['Music', 'Sports'], $users[0]->getInterests());

        // ページネーション情報の検証
        $this->assertArrayHasKey('pagination', $result);
        $this->assertEquals(1, $result['pagination']['current_page']);
        $this->assertEquals(1, $result['pagination']['total_pages']);
        $this->assertEquals(1, $result['pagination']['total_users']);
        $this->assertEquals(20, $result['pagination']['per_page']);
    }

    public function testFindAllUsersWithError()
    {
        $this->mockHandler->append(new AwsException(
            'Error retrieving users',
            new \Aws\Command('Scan'),
            ['code' => 'InternalServerError']
        ));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ユーザー一覧の取得に失敗しました: Error retrieving users');

        $this->userRepository->findAll();
    }
}
