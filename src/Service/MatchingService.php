<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\DynamoDBUserRepository;
use App\Repository\OpenSearchUserRepository;

class MatchingService
{
    private DynamoDBUserRepository $dynamoDBRepository;
    private OpenSearchUserRepository $openSearchRepository;

    public function __construct(
        DynamoDBUserRepository $dynamoDBRepository,
        OpenSearchUserRepository $openSearchRepository
    ) {
        $this->dynamoDBRepository = $dynamoDBRepository;
        $this->openSearchRepository = $openSearchRepository;
    }

    public function registerUser(User $user): void
    {
        // DynamoDBに保存
        $this->dynamoDBRepository->save($user);

        // OpenSearchにインデックス
        $this->openSearchRepository->index($user);
    }

    public function findMatches(User $user): array
    {
        $criteria = [
            'age' => [
                'min' => $user->getPreferences()['age_min'] ?? 0,
                'max' => $user->getPreferences()['age_max'] ?? 100
            ],
            'gender' => $user->getPreferences()['gender'] ?? null,
            'interests' => $user->getInterests(),
            'location' => $user->getLocation()
        ];

        // OpenSearchで検索
        $potentialMatches = $this->openSearchRepository->search($criteria);

        // 自分自身を除外
        return array_filter($potentialMatches, function($match) use ($user) {
            return $match->getId() !== $user->getId();
        });
    }

    public function getUser(string $id): ?User
    {
        return $this->dynamoDBRepository->find($id);
    }

    public function getAllUsers(): array
    {
        return $this->dynamoDBRepository->findAll();
    }

    private function isMatch(User $user1, User $user2): bool
    {
        // ユーザー1の希望条件とユーザー2の条件をチェック
        if (!$this->checkPreferences($user1->getPreferences(), $user2)) {
            return false;
        }

        // ユーザー2の希望条件とユーザー1の条件をチェック
        if (!$this->checkPreferences($user2->getPreferences(), $user1)) {
            return false;
        }

        return true;
    }

    private function checkPreferences(array $preferences, User $user): bool
    {
        // 年齢のチェック
        if (isset($preferences['age_min']) && $user->getAge() < $preferences['age_min']) {
            return false;
        }
        if (isset($preferences['age_max']) && $user->getAge() > $preferences['age_max']) {
            return false;
        }

        // 性別のチェック
        if (isset($preferences['gender']) && $user->getGender() !== $preferences['gender']) {
            return false;
        }

        return true;
    }
}
