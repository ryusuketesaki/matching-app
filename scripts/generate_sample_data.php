<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Entity\User;
use App\Repository\DynamoDBUserRepository;
use App\Repository\OpenSearchUserRepository;
use App\Service\MatchingService;
use Aws\DynamoDb\DynamoDbClient;

// 設定の読み込み
$config = require __DIR__ . '/../config/config.local.php';

// AWSクライアントの設定
$client = new DynamoDbClient([
    'region' => $config['aws']['region'],
    'version' => $config['aws']['version'],
    'endpoint' => 'http://localhost:8000', // ローカル実行用
    'credentials' => [
        'key' => $config['aws']['credentials']['key'],
        'secret' => $config['aws']['credentials']['secret']
    ]
]);

// リポジトリの設定
$dynamoDBRepository = new DynamoDBUserRepository($client, $config['dynamodb']['table']);
$openSearchRepository = new OpenSearchUserRepository(
    'http://localhost:9200', // ローカル実行用
    $config['opensearch']['index']
);

// サービスの設定
$matchingService = new MatchingService($dynamoDBRepository, $openSearchRepository);

// サンプルデータの生成
$names = ['山田', '佐藤', '鈴木', '高橋', '田中', '伊藤', '渡辺', '中村', '小林', '加藤'];
$interests = ['スポーツ', '音楽', '映画', '読書', '旅行', '料理', 'ゲーム', 'アート', '写真', 'プログラミング'];
$locations = ['東京', '大阪', '名古屋', '福岡', '札幌', '仙台', '広島', '京都', '神戸', '横浜'];

for ($i = 0; $i < 100; $i++) {
    $name = $names[array_rand($names)] . $names[array_rand($names)];
    $age = rand(18, 60);
    $gender = rand(0, 1) ? 'male' : 'female';
    $userInterests = array_rand(array_flip($interests), rand(2, 5));
    $location = $locations[array_rand($locations)];

    $user = new User(
        uniqid(),
        $name,
        $age,
        $gender,
        is_array($userInterests) ? $userInterests : [$userInterests],
        [
            'age_min' => rand(18, 30),
            'age_max' => rand(40, 60),
            'gender' => rand(0, 1) ? 'male' : 'female'
        ],
        $location
    );

    $matchingService->registerUser($user);
    echo "ユーザー {$name} を登録しました\n";
}

echo "サンプルデータの登録が完了しました\n";
