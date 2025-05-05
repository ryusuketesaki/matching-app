<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Entity\Message;
use App\Entity\User;
use App\Repository\DynamoDBMessageRepository;
use App\Repository\DynamoDBUserRepository;
use App\Service\MessageService;
use App\Service\MatchingService;
use Aws\DynamoDb\DynamoDbClient;
use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Slim\Middleware\Session;

// 設定の読み込み
$config = require __DIR__ . '/../config/config.local.php';

// 依存性注入コンテナの設定
$container = new Container();
$container->set('config', $config);

// AWSクライアントの設定
$container->set('dynamodb', function () use ($config) {
    return new \Aws\DynamoDb\DynamoDbClient([
        'region' => $config['aws']['region'],
        'version' => $config['aws']['version'],
        'endpoint' => $config['aws']['endpoint'],
        'credentials' => [
            'key' => $config['aws']['credentials']['key'],
            'secret' => $config['aws']['credentials']['secret']
        ]
    ]);
});

// リポジトリの設定
$container->set('userRepository', function ($container) use ($config) {
    return new DynamoDBUserRepository($container->get('dynamodb'), $config['dynamodb']['table']);
});

$container->set('messageRepository', function ($container) {
    return new DynamoDBMessageRepository($container->get('dynamodb'), 'messages');
});

// サービスの設定
$container->set('messageService', function ($container) {
    return new MessageService(
        $container->get('messageRepository'),
        $container->get('userRepository')
    );
});

// セッションの設定
$sessionConfig = [
    'name' => 'matching_app_session',
    'lifetime' => '24 hours',
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax',
    'autorefresh' => true,
    'gc_maxlifetime' => 86400
];

// アプリケーションの作成
$app = AppFactory::createFromContainer($container);

// ミドルウェアの追加
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);

// セッションミドルウェアの追加
$app->add(new Session($sessionConfig));

// ルートの定義
$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write(file_get_contents(__DIR__ . '/index.html'));
    return $response->withHeader('Content-Type', 'text/html');
});

$app->get('/users', function (Request $request, Response $response) use ($container) {
    try {
        $queryParams = $request->getQueryParams();

        // フィルター条件の取得
        $filters = [];
        if (!empty($queryParams['name'])) {
            $filters['name'] = $queryParams['name'];
        }
        if (!empty($queryParams['age_min'])) {
            $filters['age_min'] = (int)$queryParams['age_min'];
        }
        if (!empty($queryParams['age_max'])) {
            $filters['age_max'] = (int)$queryParams['age_max'];
        }
        if (!empty($queryParams['gender'])) {
            $filters['gender'] = $queryParams['gender'];
        }
        if (!empty($queryParams['location'])) {
            $filters['location'] = $queryParams['location'];
        }

        // ページ番号の取得
        $page = (int)($queryParams['page'] ?? 1);
        if ($page < 1) {
            $page = 1;
        }

        $result = $container->get('userRepository')->findAll($filters, $page);

        // ユーザーオブジェクトを配列に変換
        $usersArray = array_map(function($user) {
            return [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'age' => $user->getAge(),
                'gender' => $user->getGender(),
                'interests' => $user->getInterests(),
                'location' => $user->getLocation()
            ];
        }, $result['users']);

        $response->getBody()->write(json_encode([
            'users' => $usersArray,
            'pagination' => $result['pagination']
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        error_log('Error in /users endpoint: ' . $e->getMessage());
        $response->getBody()->write(json_encode([
            'error' => 'ユーザー情報の取得に失敗しました。',
            'details' => $e->getMessage()
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->post('/users', function (Request $request, Response $response) use ($container) {
    try {
        $data = json_decode($request->getBody(), true);

        // バリデーション
        if (!isset($data['name']) || empty(trim($data['name']))) {
            throw new \InvalidArgumentException('名前を入力してください。');
        }
        if (!isset($data['age']) || !is_numeric($data['age']) || $data['age'] < 0) {
            throw new \InvalidArgumentException('年齢を正しく入力してください。');
        }
        if (!isset($data['gender']) || !in_array($data['gender'], ['male', 'female'])) {
            throw new \InvalidArgumentException('性別を選択してください。');
        }
        if (!isset($data['location']) || empty(trim($data['location']))) {
            throw new \InvalidArgumentException('場所を入力してください。');
        }

        // 興味の処理
        $interests = [];
        if (isset($data['interests']) && !empty($data['interests'])) {
            if (is_string($data['interests'])) {
                $interests = array_map('trim', explode(',', $data['interests']));
            } elseif (is_array($data['interests'])) {
                $interests = array_map('trim', $data['interests']);
            }
        }

        // ユーザーの作成と保存
        $user = new User(
            uniqid(),
            trim($data['name']),
            (int)$data['age'],
            $data['gender'],
            $interests,
            trim($data['location'])
        );

        $container->get('userRepository')->save($user);

        $response->getBody()->write(json_encode([
            'id' => $user->getId(),
            'name' => $user->getName(),
            'age' => $user->getAge(),
            'gender' => $user->getGender(),
            'interests' => $user->getInterests(),
            'location' => $user->getLocation()
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\InvalidArgumentException $e) {
        $response->getBody()->write(json_encode([
            'error' => $e->getMessage()
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        error_log('Error in /users endpoint: ' . $e->getMessage());
        $response->getBody()->write(json_encode([
            'error' => 'ユーザー登録に失敗しました。',
            'details' => $e->getMessage()
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->post('/messages', function (Request $request, Response $response) use ($container) {
    try {
        $data = json_decode($request->getBody(), true);

        // バリデーション
        if (!isset($data['sender_id']) || !isset($data['receiver_id']) || !isset($data['content'])) {
            throw new \InvalidArgumentException('必須パラメータが不足しています。');
        }

        if (empty(trim($data['content']))) {
            throw new \InvalidArgumentException('メッセージ内容を入力してください。');
        }

        $message = $container->get('messageService')->sendMessage(
            $data['sender_id'],
            $data['receiver_id'],
            $data['content']
        );

        $response->getBody()->write(json_encode($message->toArray()));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\InvalidArgumentException $e) {
        $response->getBody()->write(json_encode([
            'error' => $e->getMessage()
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    } catch (\RuntimeException $e) {
        error_log('Error in /messages endpoint: ' . $e->getMessage());
        $response->getBody()->write(json_encode([
            'error' => 'メッセージの送信に失敗しました。',
            'details' => $e->getMessage()
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->get('/messages/{userId1}/{userId2}', function (Request $request, Response $response, array $args) use ($container) {
    try {
        // バリデーション
        if (empty($args['userId1']) || empty($args['userId2'])) {
            throw new \InvalidArgumentException('ユーザーIDが指定されていません。');
        }

        // ページ番号の取得
        $page = (int)($request->getQueryParams()['page'] ?? 1);
        if ($page < 1) {
            $page = 1;
        }

        $result = $container->get('messageService')->getConversation($args['userId1'], $args['userId2'], $page);

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\InvalidArgumentException $e) {
        $response->getBody()->write(json_encode([
            'error' => $e->getMessage()
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    } catch (\RuntimeException $e) {
        error_log('Error in /messages/{userId1}/{userId2} endpoint: ' . $e->getMessage());
        $response->getBody()->write(json_encode([
            'error' => 'メッセージの取得に失敗しました。',
            'details' => $e->getMessage()
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// ログインエンドポイント
$app->post('/login', function (Request $request, Response $response) use ($container) {
    try {
        $data = json_decode($request->getBody(), true);

        if (!isset($data['name']) || empty(trim($data['name']))) {
            throw new \InvalidArgumentException('名前を入力してください。');
        }
        if (!isset($data['location']) || empty(trim($data['location']))) {
            throw new \InvalidArgumentException('場所を入力してください。');
        }

        $name = trim($data['name']);
        $location = trim($data['location']);

        // ユーザーを検索
        $user = $container->get('userRepository')->findByNameAndLocation($name, $location);

        if (!$user) {
            throw new \RuntimeException('ユーザーが見つかりません。名前と場所を確認してください。');
        }

        // セッションを再生成してセキュリティを強化
        session_regenerate_id(true);

        // セッションにユーザーIDを保存
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['last_activity'] = time();

        $response->getBody()->write(json_encode([
            'id' => $user->getId(),
            'name' => $user->getName(),
            'age' => $user->getAge(),
            'gender' => $user->getGender(),
            'interests' => $user->getInterests(),
            'location' => $user->getLocation()
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\InvalidArgumentException $e) {
        error_log('Validation error in /login: ' . $e->getMessage());
        $response->getBody()->write(json_encode([
            'error' => $e->getMessage()
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    } catch (\RuntimeException $e) {
        error_log('User not found in /login: ' . $e->getMessage());
        $response->getBody()->write(json_encode([
            'error' => $e->getMessage()
        ]));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        error_log('Error in /login endpoint: ' . $e->getMessage());
        $response->getBody()->write(json_encode([
            'error' => 'ログインに失敗しました。',
            'details' => $e->getMessage()
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

// ログアウトエンドポイント
$app->post('/logout', function (Request $request, Response $response) {
    // セッションを破棄
    session_destroy();

    $response->getBody()->write(json_encode([
        'message' => 'ログアウトしました。'
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

// 現在のユーザー情報取得エンドポイント
$app->get('/current-user', function (Request $request, Response $response) use ($container) {
    try {
        // セッションの有効性をチェック
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
            throw new \RuntimeException('セッションが無効です。');
        }

        // セッションの有効期限をチェック
        $sessionLifetime = 3600; // 1時間
        if (time() - $_SESSION['last_activity'] > $sessionLifetime) {
            session_destroy();
            throw new \RuntimeException('セッションの有効期限が切れました。');
        }

        // 最終アクティビティ時間を更新
        $_SESSION['last_activity'] = time();

        $userId = $_SESSION['user_id'];
        $user = $container->get('userRepository')->findById($userId);

        if (!$user) {
            throw new \RuntimeException('ユーザーが見つかりません。');
        }

        $response->getBody()->write(json_encode([
            'id' => $user->getId(),
            'name' => $user->getName(),
            'age' => $user->getAge(),
            'gender' => $user->getGender(),
            'interests' => $user->getInterests(),
            'location' => $user->getLocation()
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (\RuntimeException $e) {
        error_log('Error in /current-user: ' . $e->getMessage());
        $response->getBody()->write(json_encode([
            'error' => $e->getMessage()
        ]));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    } catch (\Exception $e) {
        error_log('Error in /current-user endpoint: ' . $e->getMessage());
        $response->getBody()->write(json_encode([
            'error' => 'ユーザー情報の取得に失敗しました。',
            'details' => $e->getMessage()
        ]));
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

$app->run();
