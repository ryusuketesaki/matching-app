<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Entity\Message;
use App\Repository\DynamoDBMessageRepository;
use Aws\DynamoDb\DynamoDbClient;

// 設定の読み込み
$config = require __DIR__ . '/../config/config.local.php';

// DynamoDBクライアントの設定
$client = new DynamoDbClient([
    'region' => $config['aws']['region'],
    'version' => $config['aws']['version'],
    'endpoint' => $config['aws']['endpoint'],
    'credentials' => [
        'key' => $config['aws']['credentials']['key'],
        'secret' => $config['aws']['credentials']['secret']
    ]
]);

// リポジトリの初期化
$messageRepository = new DynamoDBMessageRepository($client, 'messages');

// テストユーザーのID（既存のテストユーザーを使用）
$userIds = [
    'user1' => '山田太郎',
    'user2' => '佐藤花子',
    'user3' => '鈴木一郎'
];

// メッセージの初期データ
$messages = [
    // 山田太郎 → 佐藤花子
    [
        'sender_id' => 'user1',
        'receiver_id' => 'user2',
        'content' => 'こんにちは！佐藤さん。プロフィールを拝見しました。',
        'created_at' => '2024-03-20 10:00:00'
    ],
    [
        'sender_id' => 'user2',
        'receiver_id' => 'user1',
        'content' => '山田さん、こんにちは！ありがとうございます。',
        'created_at' => '2024-03-20 10:05:00'
    ],
    [
        'sender_id' => 'user1',
        'receiver_id' => 'user2',
        'content' => '趣味の旅行について、もっと詳しく教えていただけますか？',
        'created_at' => '2024-03-20 10:10:00'
    ],

    // 佐藤花子 → 鈴木一郎
    [
        'sender_id' => 'user2',
        'receiver_id' => 'user3',
        'content' => '鈴木さん、はじめまして！',
        'created_at' => '2024-03-20 11:00:00'
    ],
    [
        'sender_id' => 'user3',
        'receiver_id' => 'user2',
        'content' => '佐藤さん、こんにちは！よろしくお願いします。',
        'created_at' => '2024-03-20 11:05:00'
    ],

    // 鈴木一郎 → 山田太郎
    [
        'sender_id' => 'user3',
        'receiver_id' => 'user1',
        'content' => '山田さん、プログラミングの経験について教えてください。',
        'created_at' => '2024-03-20 12:00:00'
    ],
    [
        'sender_id' => 'user1',
        'receiver_id' => 'user3',
        'content' => '鈴木さん、こんにちは！PHPとJavaScriptを主に使っています。',
        'created_at' => '2024-03-20 12:05:00'
    ]
];

// メッセージの登録
foreach ($messages as $messageData) {
    $message = new Message(
        uniqid(),
        $messageData['sender_id'],
        $messageData['receiver_id'],
        $messageData['content'],
        new \DateTime($messageData['created_at'])
    );

    try {
        $messageRepository->save($message);
        echo "メッセージを登録しました: {$userIds[$messageData['sender_id']]} → {$userIds[$messageData['receiver_id']]}\n";
    } catch (\Exception $e) {
        echo "エラーが発生しました: " . $e->getMessage() . "\n";
    }
}

echo "メッセージの初期データ登録が完了しました。\n";
