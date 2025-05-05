<?php

namespace Tests\Entity;

use App\Entity\Message;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    public function testMessageCreation()
    {
        $message = new Message(
            'test-id',
            'sender-id',
            'receiver-id',
            'テストメッセージ',
            '2024-03-20 12:00:00'
        );

        $this->assertEquals('test-id', $message->getId());
        $this->assertEquals('sender-id', $message->getSenderId());
        $this->assertEquals('receiver-id', $message->getReceiverId());
        $this->assertEquals('テストメッセージ', $message->getContent());
        $this->assertEquals('2024-03-20 12:00:00', $message->getCreatedAt());
    }

    public function testToArray()
    {
        $message = new Message(
            'test-id',
            'sender-id',
            'receiver-id',
            'テストメッセージ',
            '2024-03-20 12:00:00'
        );

        $expected = [
            'id' => 'test-id',
            'sender_id' => 'sender-id',
            'receiver_id' => 'receiver-id',
            'content' => 'テストメッセージ',
            'created_at' => '2024-03-20 12:00:00'
        ];

        $this->assertEquals($expected, $message->toArray());
    }

    public function testFromArray()
    {
        $data = [
            'id' => 'test-id',
            'sender_id' => 'sender-id',
            'receiver_id' => 'receiver-id',
            'content' => 'テストメッセージ',
            'created_at' => '2024-03-20 12:00:00'
        ];

        $message = Message::fromArray($data);

        $this->assertEquals('test-id', $message->getId());
        $this->assertEquals('sender-id', $message->getSenderId());
        $this->assertEquals('receiver-id', $message->getReceiverId());
        $this->assertEquals('テストメッセージ', $message->getContent());
        $this->assertEquals('2024-03-20 12:00:00', $message->getCreatedAt());
    }
}
