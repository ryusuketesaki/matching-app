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

// 既存のテーブルを削除
try {
    $client->deleteTable([
        'TableName' => 'messages'
    ]);
    echo "既存のメッセージテーブルを削除しました。\n";
    // テーブルが完全に削除されるまで待機
    $client->waitUntil('TableNotExists', [
        'TableName' => 'messages'
    ]);
} catch (\Exception $e) {
    echo "テーブル削除時のエラー: " . $e->getMessage() . "\n";
}

// メッセージテーブルの作成
try {
    $result = $client->createTable([
        'TableName' => 'messages',
        'AttributeDefinitions' => [
            [
                'AttributeName' => 'id',
                'AttributeType' => 'S'
            ],
            [
                'AttributeName' => 'conversation_id',
                'AttributeType' => 'S'
            ],
            [
                'AttributeName' => 'created_at',
                'AttributeType' => 'S'
            ]
        ],
        'KeySchema' => [
            [
                'AttributeName' => 'id',
                'KeyType' => 'HASH'
            ]
        ],
        'GlobalSecondaryIndexes' => [
            [
                'IndexName' => 'ConversationIndex',
                'KeySchema' => [
                    [
                        'AttributeName' => 'conversation_id',
                        'KeyType' => 'HASH'
                    ],
                    [
                        'AttributeName' => 'created_at',
                        'KeyType' => 'RANGE'
                    ]
                ],
                'Projection' => [
                    'ProjectionType' => 'ALL'
                ],
                'ProvisionedThroughput' => [
                    'ReadCapacityUnits' => 5,
                    'WriteCapacityUnits' => 5
                ]
            ]
        ],
        'ProvisionedThroughput' => [
            'ReadCapacityUnits' => 5,
            'WriteCapacityUnits' => 5
        ]
    ]);

    // テーブルが利用可能になるまで待機
    $client->waitUntil('TableExists', [
        'TableName' => 'messages'
    ]);

    echo "メッセージテーブルが正常に作成されました。\n";

    // テーブルの詳細を表示
    $result = $client->describeTable([
        'TableName' => 'messages'
    ]);
    echo "テーブル構造:\n";
    echo json_encode($result['Table'], JSON_PRETTY_PRINT) . "\n";
} catch (\Exception $e) {
    echo "エラーが発生しました: " . $e->getMessage() . "\n";
}
