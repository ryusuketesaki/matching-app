<?php

namespace App\Repository;

use App\Entity\Message;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;

class DynamoDBMessageRepository
{
    private DynamoDbClient $client;
    private string $tableName;
    private const MESSAGES_PER_PAGE = 20;

    public function __construct(DynamoDbClient $client, string $tableName)
    {
        $this->client = $client;
        $this->tableName = $tableName;
    }

    public function save(Message $message): Message
    {
        $item = [
            'id' => ['S' => $message->getId()],
            'sender_id' => ['S' => $message->getSenderId()],
            'receiver_id' => ['S' => $message->getReceiverId()],
            'content' => ['S' => $message->getContent()],
            'created_at' => ['S' => $message->getCreatedAt()->format('Y-m-d H:i:s')],
            'conversation_id' => ['S' => $this->generateConversationId($message->getSenderId(), $message->getReceiverId())]
        ];

        $this->client->putItem([
            'TableName' => $this->tableName,
            'Item' => $item
        ]);

        return $message;
    }

    public function findConversation(string $userId1, string $userId2, int $page = 1): array
    {
        try {
            $conversationId = $this->generateConversationId($userId1, $userId2);
            error_log("Searching for conversation: " . $conversationId);

            // 送信者と受信者の組み合わせで検索
            $result = $this->client->query([
                'TableName' => $this->tableName,
                'IndexName' => 'ConversationIndex',
                'KeyConditionExpression' => 'conversation_id = :conversation_id',
                'ExpressionAttributeValues' => [
                    ':conversation_id' => ['S' => $conversationId]
                ],
                'ScanIndexForward' => false, // 新しいメッセージを先に表示
                'Limit' => self::MESSAGES_PER_PAGE
            ]);

            error_log("Query result: " . json_encode($result));

            $messages = [];
            foreach ($result['Items'] as $item) {
                try {
                    error_log("Processing message item: " . json_encode($item));
                    $message = new Message(
                        $item['id']['S'],
                        $item['sender_id']['S'],
                        $item['receiver_id']['S'],
                        $item['content']['S'],
                        new \DateTime($item['created_at']['S'])
                    );
                    $messages[] = $message->toArray();
                } catch (\Exception $e) {
                    error_log('Error creating message object: ' . $e->getMessage());
                    continue;
                }
            }

            // ページネーション情報の計算
            $totalMessages = $this->getTotalMessages($userId1, $userId2);
            $totalPages = max(1, ceil($totalMessages / self::MESSAGES_PER_PAGE));

            // 指定されたページのメッセージを取得
            $offset = ($page - 1) * self::MESSAGES_PER_PAGE;
            $messages = array_slice($messages, $offset, self::MESSAGES_PER_PAGE);

            error_log("Returning messages: " . json_encode($messages));

            return [
                'messages' => $messages,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_messages' => $totalMessages,
                    'per_page' => self::MESSAGES_PER_PAGE,
                    'has_more' => ($page * self::MESSAGES_PER_PAGE) < $totalMessages
                ]
            ];
        } catch (DynamoDbException $e) {
            error_log('Error in findConversation: ' . $e->getMessage());
            throw new \RuntimeException('メッセージの取得に失敗しました。');
        }
    }

    private function generateConversationId(string $userId1, string $userId2): string
    {
        $ids = [$userId1, $userId2];
        sort($ids);
        return implode('_', $ids);
    }

    private function getTotalMessages(string $userId1, string $userId2): int
    {
        try {
            $result = $this->client->query([
                'TableName' => $this->tableName,
                'IndexName' => 'ConversationIndex',
                'KeyConditionExpression' => 'conversation_id = :conversation_id',
                'ExpressionAttributeValues' => [
                    ':conversation_id' => ['S' => $this->generateConversationId($userId1, $userId2)]
                ],
                'Select' => 'COUNT'
            ]);
            return $result['Count'] ?? 0;
        } catch (DynamoDbException $e) {
            error_log('Error in getTotalMessages: ' . $e->getMessage());
            return 0;
        }
    }
}
