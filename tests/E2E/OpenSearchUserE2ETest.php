<?php

namespace Tests\E2E;

use App\Entity\User;
use App\Repository\OpenSearchUserRepository;
use PHPUnit\Framework\TestCase;

class OpenSearchUserE2ETest extends TestCase
{
    private $repository;
    private static $endpoint = 'http://localhost:9200';
    private static $index = 'test_users';

    protected function setUp(): void
    {
        $this->repository = new OpenSearchUserRepository(self::$endpoint, self::$index);
    }

    public function testIndexAndSearchUser(): void
    {
        $user = new User('e2e-1', 'E2Eユーザー', 28, 'female', ['sports', 'music'], 'osaka');
        $this->repository->index($user);
        // 検索条件
        $criteria = [
            'age' => ['min' => 25, 'max' => 30],
            'gender' => 'female',
            'interests' => ['sports'],
            'location' => 'osaka'
        ];
        // OpenSearchのインデックス反映待ち
        sleep(1);
        $results = $this->repository->search($criteria);
        $this->assertNotEmpty($results);
        $found = false;
        foreach ($results as $result) {
            if ($result->getId() === 'e2e-1') {
                $found = true;
                $this->assertEquals('E2Eユーザー', $result->getName());
                $this->assertEquals(28, $result->getAge());
                $this->assertEquals('female', $result->getGender());
                $this->assertContains('sports', $result->getInterests());
                $this->assertEquals('osaka', $result->getLocation());
            }
        }
        $this->assertTrue($found, 'E2Eユーザーが検索結果に含まれていること');
    }
}
