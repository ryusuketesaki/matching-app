<?php

require __DIR__ . '/../vendor/autoload.php';

use Aws\DynamoDb\DynamoDbClient;

// 設定の読み込み
$config = require __DIR__ . '/../config/config.local.php';

// DynamoDBクライアントの作成
$client = new DynamoDbClient([
    'region' => $config['aws']['region'],
    'version' => $config['aws']['version'],
    'endpoint' => $config['aws']['endpoint'],
    'credentials' => [
        'key' => $config['aws']['credentials']['key'],
        'secret' => $config['aws']['credentials']['secret']
    ]
]);

// 既存のusersテーブルを削除
try {
    $client->deleteTable([
        'TableName' => 'users'
    ]);
    echo "既存のusersテーブルを削除しました。\n";
    $client->waitUntil('TableNotExists', [
        'TableName' => 'users'
    ]);
} catch (\Exception $e) {
    echo "テーブル削除時のエラー: " . $e->getMessage() . "\n";
}

// usersテーブルの作成
try {
    $result = $client->createTable([
        'TableName' => 'users',
        'AttributeDefinitions' => [
            [
                'AttributeName' => 'id',
                'AttributeType' => 'S'
            ]
        ],
        'KeySchema' => [
            [
                'AttributeName' => 'id',
                'KeyType' => 'HASH'
            ]
        ],
        'ProvisionedThroughput' => [
            'ReadCapacityUnits' => 5,
            'WriteCapacityUnits' => 5
        ]
    ]);

    $client->waitUntil('TableExists', [
        'TableName' => 'users'
    ]);

    echo "usersテーブルが正常に作成されました。\n";
    $result = $client->describeTable([
        'TableName' => 'users'
    ]);
    echo "テーブル構造:\n";
    echo json_encode($result['Table'], JSON_PRETTY_PRINT) . "\n";
} catch (\Exception $e) {
    echo "エラーが発生しました: " . $e->getMessage() . "\n";
}
