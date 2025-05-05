<?php

return [
    'aws' => [
        'region' => 'ap-northeast-1',
        'version' => 'latest',
    ],
    'opensearch' => [
        'endpoint' => 'https://your-opensearch-endpoint',
        'index' => 'matching_users',
    ],
    'dynamodb' => [
        'table' => 'matching_users',
    ],
    'app' => [
        'env' => 'development',
        'debug' => true,
    ],
];
