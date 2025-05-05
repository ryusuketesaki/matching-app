<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entity\User;
use App\Repository\DynamoDBUserRepository;
use Aws\DynamoDb\DynamoDbClient;

// DynamoDBクライアントの初期化
$client = new DynamoDbClient([
    'region' => 'ap-northeast-1',
    'version' => 'latest',
    'endpoint' => 'http://dynamodb-local:8000',
    'credentials' => [
        'key' => 'dummy',
        'secret' => 'dummy'
    ]
]);

// リポジトリの初期化
$userRepository = new DynamoDBUserRepository($client, 'users');

// テストユーザーの作成
$users = [
    new User(
        'user1',
        '山田太郎',
        25,
        'male',
        ['プログラミング', '旅行'],
        '東京'
    ),
    new User(
        'user2',
        '佐藤花子',
        22,
        'female',
        ['読書', '料理'],
        '大阪'
    ),
    new User(
        'user3',
        '鈴木一郎',
        30,
        'male',
        ['スポーツ', '音楽'],
        '名古屋'
    )
];

// ユーザーの登録
foreach ($users as $user) {
    try {
        $userRepository->save($user);
        echo "ユーザー {$user->getName()} を登録しました。\n";
    } catch (\Exception $e) {
        echo "ユーザー {$user->getName()} の登録に失敗しました: {$e->getMessage()}\n";
    }
}

echo "初期データの登録が完了しました。\n";
