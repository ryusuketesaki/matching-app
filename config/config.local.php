<?php

return [
    'aws' => [
        'region' => 'ap-northeast-1',
        'version' => 'latest',
        'endpoint' => getenv('AWS_ENDPOINT') ?: 'http://dynamodb-local:8000',
        'credentials' => [
            'key' => 'dummy',
            'secret' => 'dummy'
        ]
    ],
    'opensearch' => [
        'endpoint' => getenv('OPENSEARCH_ENDPOINT') ?: 'http://opensearch:9200',
        'index' => 'matching_users',
    ],
    'dynamodb' => [
        'table' => 'users'
    ],
    'app' => [
        'env' => 'development',
        'debug' => true,
    ],
];
