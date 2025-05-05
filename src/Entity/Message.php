<?php

namespace App\Entity;

class Message
{
    private string $id;
    private string $senderId;
    private string $receiverId;
    private string $content;
    private \DateTime $createdAt;

    public function __construct(
        string $id,
        string $senderId,
        string $receiverId,
        string $content,
        \DateTime $createdAt
    ) {
        $this->id = $id;
        $this->senderId = $senderId;
        $this->receiverId = $receiverId;
        $this->content = $content;
        $this->createdAt = $createdAt;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSenderId(): string
    {
        return $this->senderId;
    }

    public function getReceiverId(): string
    {
        return $this->receiverId;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sender_id' => $this->senderId,
            'receiver_id' => $this->receiverId,
            'content' => $this->content,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s')
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['sender_id'],
            $data['receiver_id'],
            $data['content'],
            new \DateTime($data['created_at'])
        );
    }
}
