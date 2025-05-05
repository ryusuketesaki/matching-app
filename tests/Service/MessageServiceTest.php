<?php

namespace Tests\Service;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\DynamoDBMessageRepository;
use App\Repository\DynamoDBUserRepository;
use App\Service\MessageService;
use PHPUnit\Framework\TestCase;

class MessageServiceTest extends TestCase
{
    private MessageService $service;
    private DynamoDBMessageRepository $messageRepository;
    private DynamoDBUserRepository $userRepository;

    protected function setUp(): void
    {
        $this->messageRepository = $this->createMock(DynamoDBMessageRepository::class);
        $this->userRepository = $this->createMock(DynamoDBUserRepository::class);
        $this->service = new MessageService($this->messageRepository, $this->userRepository);
    }

    public function testSendMessage()
    {
        $sender = new User('sender-id', '送信者', 25, 'male', ['プログラミング'], '東京');
        $receiver = new User('receiver-id', '受信者', 30, 'female', ['読書'], '大阪');

        $this->userRepository->method('findById')
            ->willReturnCallback(function ($id) use ($sender, $receiver) {
                return $id === 'sender-id' ? $sender : $receiver;
            });

        $this->messageRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Message $message) {
                return $message->getSenderId() === 'sender-id' &&
                       $message->getReceiverId() === 'receiver-id' &&
                       $message->getContent() === 'テストメッセージ';
            }));

        $message = $this->service->sendMessage('sender-id', 'receiver-id', 'テストメッセージ');

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals('sender-id', $message->getSenderId());
        $this->assertEquals('receiver-id', $message->getReceiverId());
        $this->assertEquals('テストメッセージ', $message->getContent());
    }

    public function testSendMessageWithNonExistentUser()
    {
        $this->userRepository->method('findById')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User not found');

        $this->service->sendMessage('non-existent-id', 'receiver-id', 'テストメッセージ');
    }

    public function testGetConversation()
    {
        $messages = [
            new Message('msg1', 'user1', 'user2', 'こんにちは', '2024-03-20 12:00:00'),
            new Message('msg2', 'user2', 'user1', 'こんにちは！', '2024-03-20 12:01:00')
        ];

        $this->messageRepository->expects($this->once())
            ->method('findByConversation')
            ->with('user1', 'user2')
            ->willReturn($messages);

        $result = $this->service->getConversation('user1', 'user2');

        $this->assertCount(2, $result);
        $this->assertEquals('msg1', $result[0]->getId());
        $this->assertEquals('msg2', $result[1]->getId());
    }
}
