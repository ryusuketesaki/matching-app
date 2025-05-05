<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Entity\User;
use App\Repository\DynamoDBUserRepository;
use App\Repository\OpenSearchUserRepository;
use Aws\DynamoDb\DynamoDbClient;

// DynamoDBクライアントの設定
$client = new DynamoDbClient([
    'region' => 'ap-northeast-1',
    'version' => 'latest',
    'endpoint' => 'http://dynamodb-local:8000',
    'credentials' => [
        'key' => 'dummy',
        'secret' => 'dummy'
    ]
]);

$userRepository = new DynamoDBUserRepository($client, 'users');

// OpenSearchリポジトリの設定
$opensearchEndpoint = getenv('OPENSEARCH_ENDPOINT') ?: 'https://opensearch:9200';
$opensearchIndex = getenv('OPENSEARCH_INDEX') ?: 'users';
$openSearchRepository = new OpenSearchUserRepository($opensearchEndpoint, $opensearchIndex, 'admin', 'Gpt4!Secure2024$');

// テストデータの設定
$names = ['山田', '佐藤', '鈴木', '田中', '高橋', '伊藤', '渡辺', '中村', '小林', '加藤'];
$firstNames = ['太郎', '花子', '一郎', '次郎', '三郎', '四郎', '五郎', '六郎', '七郎', '八郎'];
$locations = ['東京', '大阪', '名古屋', '福岡', '札幌', '仙台', '広島', '京都', '神戸', '横浜'];
$interests = [
    ['スポーツ', '旅行'],
    ['音楽', '読書'],
    ['料理', '映画'],
    ['ゲーム', 'アニメ'],
    ['写真', 'カメラ'],
    ['登山', 'キャンプ'],
    ['釣り', 'ゴルフ'],
    ['テニス', 'サッカー'],
    ['野球', 'バスケットボール'],
    ['水泳', 'ジョギング']
];

// 10000件のテストユーザーを生成
$totalUsers = 10000;
$batchSize = 100; // 一度に保存するユーザー数
$users = [];

for ($i = 0; $i < $totalUsers; $i++) {
    $name = $names[array_rand($names)] . $firstNames[array_rand($firstNames)];
    $age = rand(18, 80);
    $gender = rand(0, 1) ? 'male' : 'female';
    $location = $locations[array_rand($locations)];
    $userInterests = $interests[array_rand($interests)];

    $user = new User(
        uniqid(),
        $name,
        $age,
        $gender,
        $userInterests,
        $location
    );

    $users[] = $user;

    // バッチサイズに達したら保存
    if (count($users) >= $batchSize || $i === $totalUsers - 1) {
        foreach ($users as $user) {
            try {
                $userRepository->save($user);
                echo "ユーザーを保存しました: {$user->getName()}\n";
            } catch (\Exception $e) {
                echo "DynamoDBエラー: {$e->getMessage()}\n";
            }
            // OpenSearchにも登録
            try {
                $openSearchRepository->index($user);
                echo "OpenSearchにも登録: {$user->getName()}\n";
            } catch (\Exception $e) {
                echo "OpenSearchエラー: {$e->getMessage()}\n";
            }
        }
        $users = [];
    }
}

echo "テストユーザーの生成が完了しました。\n";
