<?php

namespace App\Repository;

use App\Entity\User;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;

class DynamoDBUserRepository
{
    private $client;
    private $tableName;
    private const USERS_PER_PAGE = 20;

    public function __construct(DynamoDbClient $client, string $tableName)
    {
        $this->client = $client;
        $this->tableName = $tableName;
    }

    public function save(User $user): void
    {
        try {
            $this->client->putItem([
                'TableName' => $this->tableName,
                'Item' => [
                    'id' => ['S' => $user->getId()],
                    'name' => ['S' => $user->getName()],
                    'age' => ['N' => (string)$user->getAge()],
                    'gender' => ['S' => $user->getGender()],
                    'interests' => ['SS' => $user->getInterests()],
                    'location' => ['S' => $user->getLocation()]
                ]
            ]);
        } catch (DynamoDbException $e) {
            throw new \RuntimeException('ユーザーの保存に失敗しました: ' . $e->getMessage());
        }
    }

    public function find(string $id): ?User
    {
        try {
            $result = $this->client->getItem([
                'TableName' => $this->tableName,
                'Key' => [
                    'id' => ['S' => $id]
                ]
            ]);

            if (!isset($result['Item'])) {
                return null;
            }

            return $this->createUserFromItem($result['Item']);
        } catch (DynamoDbException $e) {
            throw new \RuntimeException('Failed to find user: ' . $e->getMessage());
        }
    }

    public function findAll(array $filters = [], int $page = 1): array
    {
        try {
            $scanParams = [
                'TableName' => $this->tableName
            ];

            // フィルター条件の構築
            $filterExpressions = [];
            $expressionAttributeNames = [];
            $expressionAttributeValues = [];

            if (!empty($filters['name'])) {
                $filterExpressions[] = 'contains(#name, :name)';
                $expressionAttributeNames['#name'] = 'name';
                $expressionAttributeValues[':name'] = ['S' => $filters['name']];
            }

            if (!empty($filters['age_min'])) {
                $filterExpressions[] = '#age >= :age_min';
                $expressionAttributeNames['#age'] = 'age';
                $expressionAttributeValues[':age_min'] = ['N' => (string)$filters['age_min']];
            }

            if (!empty($filters['age_max'])) {
                $filterExpressions[] = '#age <= :age_max';
                $expressionAttributeNames['#age'] = 'age';
                $expressionAttributeValues[':age_max'] = ['N' => (string)$filters['age_max']];
            }

            if (!empty($filters['gender'])) {
                $filterExpressions[] = '#gender = :gender';
                $expressionAttributeNames['#gender'] = 'gender';
                $expressionAttributeValues[':gender'] = ['S' => $filters['gender']];
            }

            if (!empty($filters['location'])) {
                $filterExpressions[] = 'contains(#location, :location)';
                $expressionAttributeNames['#location'] = 'location';
                $expressionAttributeValues[':location'] = ['S' => $filters['location']];
            }

            if (!empty($filterExpressions)) {
                $scanParams['FilterExpression'] = implode(' AND ', $filterExpressions);
                $scanParams['ExpressionAttributeNames'] = $expressionAttributeNames;
                $scanParams['ExpressionAttributeValues'] = $expressionAttributeValues;
            }

            $result = $this->client->scan($scanParams);

            $users = [];
            foreach ($result['Items'] as $item) {
                $users[] = $this->createUserFromItem($item);
            }

            // ページング処理
            $totalUsers = count($users);
            $totalPages = ceil($totalUsers / self::USERS_PER_PAGE);
            $page = max(1, min($page, $totalPages));
            $offset = ($page - 1) * self::USERS_PER_PAGE;
            $users = array_slice($users, $offset, self::USERS_PER_PAGE);

            return [
                'users' => $users,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_users' => $totalUsers,
                    'per_page' => self::USERS_PER_PAGE
                ]
            ];
        } catch (DynamoDbException $e) {
            throw new \RuntimeException('ユーザー一覧の取得に失敗しました: ' . $e->getAwsErrorMessage());
        } catch (\Exception $e) {
            throw new \RuntimeException('ユーザー一覧の取得に失敗しました: ' . $e->getMessage());
        }
    }

    public function findById(string $id): ?User
    {
        try {
            $result = $this->client->getItem([
                'TableName' => $this->tableName,
                'Key' => [
                    'id' => ['S' => $id]
                ]
            ]);

            if (!isset($result['Item'])) {
                return null;
            }

            return $this->createUserFromItem($result['Item']);
        } catch (DynamoDbException $e) {
            throw new \RuntimeException('ユーザーの取得に失敗しました: ' . $e->getMessage());
        }
    }

    public function findByNameAndLocation(string $name, string $location): ?User
    {
        try {
            $result = $this->client->scan([
                'TableName' => $this->tableName,
                'FilterExpression' => '#name = :name AND #location = :location',
                'ExpressionAttributeNames' => [
                    '#name' => 'name',
                    '#location' => 'location'
                ],
                'ExpressionAttributeValues' => [
                    ':name' => ['S' => $name],
                    ':location' => ['S' => $location]
                ]
            ]);

            if (empty($result['Items'])) {
                return null;
            }

            return $this->createUserFromItem($result['Items'][0]);
        } catch (DynamoDbException $e) {
            throw new \RuntimeException('ユーザーの検索に失敗しました: ' . $e->getMessage());
        }
    }

    private function createUserFromItem(array $item): User
    {
        return new User(
            $item['id']['S'],
            $item['name']['S'],
            (int)$item['age']['N'],
            $item['gender']['S'],
            isset($item['interests']['SS']) ? $item['interests']['SS'] : [],
            $item['location']['S']
        );
    }
}
