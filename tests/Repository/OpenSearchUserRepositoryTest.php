<?php

namespace Tests\Repository;

use App\Entity\User;
use App\Repository\OpenSearchUserRepository;
use PHPUnit\Framework\TestCase;
use Elasticsearch\Client;

class OpenSearchUserRepositoryTest extends TestCase
{
    private $clientMock;
    private $repository;

    protected function setUp(): void
    {
        $this->clientMock = $this->createMock(Client::class);
        $this->repository = $this->getMockBuilder(OpenSearchUserRepository::class)
            ->setConstructorArgs(['http://localhost:9200', 'users'])
            ->onlyMethods(['getClient'])
            ->getMock();
        $this->repository->method('getClient')->willReturn($this->clientMock);
    }

    public function testIndexUser(): void
    {
        $user = new User('1', 'テストユーザー', 25, 'male', ['music'], 'tokyo');
        $this->clientMock->expects($this->once())
            ->method('index')
            ->with($this->arrayHasKey('body'));
        // indexメソッドを直接呼び出し
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('index');
        $method->setAccessible(true);
        $method->invoke($this->repository, $user);
    }

    public function testSearchUser(): void
    {
        $criteria = [
            'age' => ['min' => 20, 'max' => 30],
            'gender' => 'male',
            'interests' => ['music'],
            'location' => 'tokyo'
        ];
        $mockResponse = [
            'hits' => [
                'hits' => [
                    ['_source' => [
                        'id' => '1',
                        'name' => 'テストユーザー',
                        'age' => 25,
                        'gender' => 'male',
                        'interests' => ['music'],
                        'location' => 'tokyo'
                    ]]
                ]
            ]
        ];
        $this->clientMock->expects($this->once())
            ->method('search')
            ->willReturn($mockResponse);
        // searchメソッドを直接呼び出し
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('search');
        $method->setAccessible(true);
        $result = $method->invoke($this->repository, $criteria);
        $this->assertIsArray($result);
        $this->assertInstanceOf(User::class, $result[0]);
        $this->assertEquals('テストユーザー', $result[0]->getName());
    }
}
