<?php

namespace Tests\Unit;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\DynamoDBMessageRepository;
use App\Repository\DynamoDBUserRepository;
use App\Service\MessageService;
use PHPUnit\Framework\TestCase;
use Mockery;

class MessageServiceTest extends TestCase
{
    private $messageRepository;
    private $userRepository;
    private $messageService;

    protected function setUp(): void
    {
        $this->messageRepository = Mockery::mock(DynamoDBMessageRepository::class);
        $this->userRepository = Mockery::mock(DynamoDBUserRepository::class);
        $this->messageService = new MessageService($this->messageRepository, $this->userRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testSendMessageSuccess()
    {
        $senderId = 'sender123';
        $receiverId = 'receiver123';
        $content = 'テストメッセージ';

        $sender = new User($senderId, '送信者', 25, 'male', ['スポーツ'], '東京');
        $receiver = new User($receiverId, '受信者', 30, 'female', ['音楽'], '大阪');

        $this->userRepository->shouldReceive('findById')
            ->with($senderId)
            ->andReturn($sender);

        $this->userRepository->shouldReceive('findById')
            ->with($receiverId)
            ->andReturn($receiver);

        $this->messageRepository->shouldReceive('save')
            ->once()
            ->andReturnUsing(function ($message) {
                return $message;
            });

        $message = $this->messageService->sendMessage($senderId, $receiverId, $content);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals($senderId, $message->getSenderId());
        $this->assertEquals($receiverId, $message->getReceiverId());
        $this->assertEquals($content, $message->getContent());
    }

    public function testSendMessageWithInvalidSender()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('送信者が存在しません。');

        $senderId = 'invalid_sender';
        $receiverId = 'receiver123';
        $content = 'テストメッセージ';

        $this->userRepository->shouldReceive('findById')
            ->with($senderId)
            ->andReturn(null);

        $this->messageService->sendMessage($senderId, $receiverId, $content);
    }

    public function testSendMessageWithInvalidReceiver()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('受信者が存在しません。');

        $senderId = 'sender123';
        $receiverId = 'invalid_receiver';
        $content = 'テストメッセージ';

        $sender = new User($senderId, '送信者', 25, 'male', ['スポーツ'], '東京');

        $this->userRepository->shouldReceive('findById')
            ->with($senderId)
            ->andReturn($sender);

        $this->userRepository->shouldReceive('findById')
            ->with($receiverId)
            ->andReturn(null);

        $this->messageService->sendMessage($senderId, $receiverId, $content);
    }

    public function testGetConversationSuccess()
    {
        $userId1 = 'user1';
        $userId2 = 'user2';

        $messages = [
            new Message('msg1', $userId1, $userId2, 'メッセージ1', new \DateTime()),
            new Message('msg2', $userId2, $userId1, 'メッセージ2', new \DateTime())
        ];

        $this->messageRepository->shouldReceive('findConversation')
            ->with($userId1, $userId2)
            ->andReturn($messages);

        $result = $this->messageService->getConversation($userId1, $userId2);

        $this->assertCount(2, $result);
        $this->assertEquals('メッセージ1', $result[0]->getContent());
        $this->assertEquals('メッセージ2', $result[1]->getContent());
    }

    public function testGetConversationWithEmptyResult()
    {
        $userId1 = 'user1';
        $userId2 = 'user2';

        $this->messageRepository->shouldReceive('findConversation')
            ->with($userId1, $userId2)
            ->andReturn([]);

        $result = $this->messageService->getConversation($userId1, $userId2);

        $this->assertEmpty($result);
    }
}
