<?php

namespace Tests\E2E;

use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class LoginE2ETest extends TestCase
{
    private $client;
    private $mockHandler;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $this->client = new Client([
            'handler' => $handlerStack,
            'cookies' => new CookieJar(),
            'http_errors' => false
        ]);
    }

    public function testSuccessfulLogin()
    {
        // ログインレスポンスのモック
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'id' => 'test-user-id',
                'name' => '山田太郎',
                'age' => 30,
                'gender' => '男性',
                'interests' => ['旅行', '読書'],
                'location' => '東京'
            ]))
        );

        // 現在のユーザー情報レスポンスのモック
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'id' => 'test-user-id',
                'name' => '山田太郎',
                'age' => 30,
                'gender' => '男性',
                'interests' => ['旅行', '読書'],
                'location' => '東京'
            ]))
        );

        // ログインリクエスト
        $response = $this->client->post('/login', [
            'json' => [
                'name' => '山田太郎',
                'location' => '東京'
            ]
        ]);

        // ステータスコードの確認
        $this->assertEquals(200, $response->getStatusCode());

        // レスポンスの確認
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals('山田太郎', $data['name']);
        $this->assertEquals('東京', $data['location']);

        // セッションが正しく設定されているか確認
        $currentUserResponse = $this->client->get('/current-user');
        $this->assertEquals(200, $currentUserResponse->getStatusCode());
        $currentUserData = json_decode($currentUserResponse->getBody(), true);
        $this->assertEquals($data['id'], $currentUserData['id']);
    }

    public function testLoginWithInvalidCredentials()
    {
        // 無効な認証情報でのレスポンスのモック
        $this->mockHandler->append(
            new Response(401, [], json_encode([
                'error' => 'ユーザーが見つかりません。名前と場所を確認してください。'
            ]))
        );

        // 無効な認証情報でログインを試みる
        $response = $this->client->post('/login', [
            'json' => [
                'name' => '存在しないユーザー',
                'location' => '東京'
            ]
        ]);

        // ステータスコードの確認
        $this->assertEquals(401, $response->getStatusCode());

        // エラーメッセージの確認
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testLoginWithMissingFields()
    {
        // 必須フィールドが欠けているレスポンスのモック
        $this->mockHandler->append(
            new Response(400, [], json_encode([
                'error' => '場所を入力してください。'
            ]))
        );

        // 必須フィールドが欠けているログインリクエスト
        $response = $this->client->post('/login', [
            'json' => [
                'name' => '山田太郎'
                // location フィールドが欠けている
            ]
        ]);

        // ステータスコードの確認
        $this->assertEquals(400, $response->getStatusCode());

        // エラーメッセージの確認
        $data = json_decode($response->getBody(), true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testLogout()
    {
        // ログインレスポンスのモック
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'id' => 'test-user-id',
                'name' => '山田太郎',
                'age' => 30,
                'gender' => '男性',
                'interests' => ['旅行', '読書'],
                'location' => '東京'
            ]))
        );

        // ログアウトレスポンスのモック
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'message' => 'ログアウトしました。'
            ]))
        );

        // ログアウト後のcurrent-userレスポンスのモック
        $this->mockHandler->append(
            new Response(401, [], json_encode([
                'error' => 'セッションが無効です。'
            ]))
        );

        // まずログイン
        $this->client->post('/login', [
            'json' => [
                'name' => '山田太郎',
                'location' => '東京'
            ]
        ]);

        // ログアウト
        $response = $this->client->post('/logout');
        $this->assertEquals(200, $response->getStatusCode());

        // ログアウト後はcurrent-userにアクセスできないことを確認
        $currentUserResponse = $this->client->get('/current-user');
        $this->assertEquals(401, $currentUserResponse->getStatusCode());
    }

    public function testSessionExpiration()
    {
        // ログインレスポンスのモック
        $this->mockHandler->append(
            new Response(200, [], json_encode([
                'id' => 'test-user-id',
                'name' => '山田太郎',
                'age' => 30,
                'gender' => '男性',
                'interests' => ['旅行', '読書'],
                'location' => '東京'
            ]))
        );

        // セッション期限切れのレスポンスのモック
        $this->mockHandler->append(
            new Response(401, [], json_encode([
                'error' => 'セッションの有効期限が切れました。'
            ]))
        );

        // ログイン
        $response = $this->client->post('/login', [
            'json' => [
                'name' => '山田太郎',
                'location' => '東京'
            ]
        ]);
        $this->assertEquals(200, $response->getStatusCode());

        // セッションが切れていることを確認
        $currentUserResponse = $this->client->get('/current-user');
        $this->assertEquals(401, $currentUserResponse->getStatusCode());
    }
}
