<?php

namespace Tests\Repository;

use App\Entity\Message;
use App\Repository\DynamoDBMessageRepository;
use Aws\DynamoDb\DynamoDbClient;
use PHPUnit\Framework\TestCase;
use Aws\MockHandler;
use Aws\Result;

class DynamoDBMessageRepositoryTest extends TestCase
{
    private DynamoDBMessageRepository $repository;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $client = new DynamoDbClient([
            'region' => 'us-east-1',
            'version' => 'latest',
            'handler' => $this->mockHandler
        ]);

        $this->repository = new DynamoDBMessageRepository($client, 'test-messages');
    }

    public function testSave()
    {
        $message = new Message(
            'test-id',
            'sender-id',
            'receiver-id',
            'テストメッセージ',
            '2024-03-20 12:00:00'
        );

        $this->mockHandler->append(new Result([
            'Attributes' => [
                'id' => ['S' => 'test-id'],
                'sender_id' => ['S' => 'sender-id'],
                'receiver_id' => ['S' => 'receiver-id'],
                'content' => ['S' => 'テストメッセージ'],
                'created_at' => ['S' => '2024-03-20 12:00:00']
            ]
        ]));

        $this->repository->save($message);
        $this->assertTrue(true); // 例外が発生しなければ成功
    }

    public function testFindByConversation()
    {
        $this->mockHandler->append(new Result([
            'Items' => [
                [
                    'id' => ['S' => 'test-id-1'],
                    'sender_id' => ['S' => 'user1'],
                    'receiver_id' => ['S' => 'user2'],
                    'content' => ['S' => 'こんにちは'],
                    'created_at' => ['S' => '2024-03-20 12:00:00']
                ],
                [
                    'id' => ['S' => 'test-id-2'],
                    'sender_id' => ['S' => 'user2'],
                    'receiver_id' => ['S' => 'user1'],
                    'content' => ['S' => 'こんにちは！'],
                    'created_at' => ['S' => '2024-03-20 12:01:00']
                ]
            ]
        ]));

        $messages = $this->repository->findByConversation('user1', 'user2');

        $this->assertCount(2, $messages);
        $this->assertInstanceOf(Message::class, $messages[0]);
        $this->assertInstanceOf(Message::class, $messages[1]);
        $this->assertEquals('test-id-1', $messages[0]->getId());
        $this->assertEquals('test-id-2', $messages[1]->getId());
    }
}
