<?php

namespace App\Repository;

use App\Entity\User;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

class OpenSearchUserRepository
{
    private Client $client;
    private string $index;

    public function __construct(string $endpoint, string $index)
    {
        $this->client = ClientBuilder::create()
            ->setHosts([$endpoint])
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

        $this->client->index($params);
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
