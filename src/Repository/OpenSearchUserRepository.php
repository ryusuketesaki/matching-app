<?php

namespace App\Repository;

use App\Entity\User;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

class OpenSearchUserRepository
{
    private Client $client;
    private string $index;
    private string $username;
    private string $password;

    public function __construct(string $endpoint, string $index, string $username = 'admin', string $password = 'Gpt4!Secure2024$')
    {
        $this->username = $username;
        $this->password = $password;
        $this->client = ClientBuilder::create()
            ->setHosts([$endpoint])
            ->setBasicAuthentication($this->username, $this->password)
            ->setSSLVerification(false)
            ->build();
        $this->index = $index;
    }

    public function index(User $user): void
    {
        $params = [
            'index' => $this->index,
            'id' => $user->getId(),
            'body' => $user->toArray()
        ];

        try {
            $this->client->index($params);
        } catch (\Exception $e) {
            // インデックスが存在しない場合は自動作成
            if (strpos($e->getMessage(), 'index_not_found_exception') !== false) {
                $this->client->indices()->create([
                    'index' => $this->index,
                    'body' => [
                        'settings' => [
                            'number_of_shards' => 1
                        ],
                        'mappings' => [
                            'properties' => [
                                'id' =>    [ 'type' => 'keyword' ],
                                'name' =>  [ 'type' => 'text' ],
                                'age' =>   [ 'type' => 'integer' ],
                                'gender' => [ 'type' => 'keyword' ],
                                'interests' => [ 'type' => 'keyword' ],
                                'location' => [ 'type' => 'keyword' ]
                            ]
                        ]
                    ]
                ]);
                // 再度データ投入
                $this->client->index($params);
            } else {
                throw $e;
            }
        }
    }

    public function search(array $criteria): array
    {
        $query = [
            'bool' => [
                'must' => []
            ]
        ];

        if (isset($criteria['age'])) {
            $query['bool']['must'][] = [
                'range' => [
                    'age' => [
                        'gte' => $criteria['age']['min'] ?? 0,
                        'lte' => $criteria['age']['max'] ?? 100
                    ]
                ]
            ];
        }

        if (isset($criteria['gender'])) {
            $query['bool']['must'][] = [
                'term' => [
                    'gender' => $criteria['gender']
                ]
            ];
        }

        if (isset($criteria['interests'])) {
            $query['bool']['must'][] = [
                'terms' => [
                    'interests' => $criteria['interests']
                ]
            ];
        }

        if (isset($criteria['location'])) {
            $query['bool']['must'][] = [
                'term' => [
                    'location' => $criteria['location']
                ]
            ];
        }

        $params = [
            'index' => $this->index,
            'body' => [
                'query' => $query
            ]
        ];

        $response = $this->client->search($params);

        $users = [];
        foreach ($response['hits']['hits'] as $hit) {
            $users[] = User::fromArray($hit['_source']);
        }

        return $users;
    }
}
