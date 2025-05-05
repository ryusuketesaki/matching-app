<?php

namespace App\Service;

use App\Entity\Message;
use App\Entity\User;
use App\Repository\DynamoDBMessageRepository;
use App\Repository\DynamoDBUserRepository;
use RuntimeException;

class MessageService
{
    private DynamoDBMessageRepository $messageRepository;
    private DynamoDBUserRepository $userRepository;

    public function __construct(
        DynamoDBMessageRepository $messageRepository,
        DynamoDBUserRepository $userRepository
    ) {
        $this->messageRepository = $messageRepository;
        $this->userRepository = $userRepository;
    }

    public function sendMessage(string $senderId, string $receiverId, string $content): Message
    {
        // 送信者の存在確認
        $sender = $this->userRepository->findById($senderId);
        if (!$sender) {
            throw new RuntimeException('送信者が存在しません。');
        }

        // 受信者の存在確認
        $receiver = $this->userRepository->findById($receiverId);
        if (!$receiver) {
            throw new RuntimeException('受信者が存在しません。');
        }

        // メッセージ内容のバリデーション
        $content = trim($content);
        if (empty($content)) {
            throw new RuntimeException('メッセージ内容を入力してください。');
        }

        if (mb_strlen($content) > 1000) {
            throw new RuntimeException('メッセージは1000文字以内で入力してください。');
        }

        // メッセージの作成と保存
        $message = new Message(
            uniqid(),
            $senderId,
            $receiverId,
            $content,
            new \DateTime()
        );

        try {
            return $this->messageRepository->save($message);
        } catch (\Exception $e) {
            throw new RuntimeException('メッセージの送信に失敗しました。', 0, $e);
        }
    }

    public function getConversation(string $userId1, string $userId2, int $page = 1): array
    {
        try {
            return $this->messageRepository->findConversation($userId1, $userId2, $page);
        } catch (\Exception $e) {
            throw new RuntimeException('メッセージの取得に失敗しました。', 0, $e);
        }
    }
}
