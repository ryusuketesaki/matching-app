<?php

namespace Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserCreation()
    {
        $user = new User(
            'test-id',
            'テストユーザー',
            25,
            'male',
            ['プログラミング', '読書'],
            '東京'
        );

        $this->assertEquals('test-id', $user->getId());
        $this->assertEquals('テストユーザー', $user->getName());
        $this->assertEquals(25, $user->getAge());
        $this->assertEquals('male', $user->getGender());
        $this->assertEquals(['プログラミング', '読書'], $user->getInterests());
        $this->assertEquals('東京', $user->getLocation());
    }

    public function testToArray()
    {
        $user = new User(
            'test-id',
            'テストユーザー',
            25,
            'male',
            ['プログラミング', '読書'],
            '東京'
        );

        $expected = [
            'id' => 'test-id',
            'name' => 'テストユーザー',
            'age' => 25,
            'gender' => 'male',
            'interests' => ['プログラミング', '読書'],
            'location' => '東京'
        ];

        $this->assertEquals($expected, $user->toArray());
    }

    public function testFromArray()
    {
        $data = [
            'id' => 'test-id',
            'name' => 'テストユーザー',
            'age' => 25,
            'gender' => 'male',
            'interests' => ['プログラミング', '読書'],
            'location' => 'e
            '
        ];

        $user = User::fromArray($data);

        $this->assertEquals('test-id', $user->getId());
        $this->assertEquals('テストユーザー', $user->getName());
        $this->assertEquals(25, $user->getAge());
        $this->assertEquals('male', $user->getGender());
        $this->assertEquals(['プログラミング', '読書'], $user->getInterests());
        $this->assertEquals('東京', $user->getLocation());
    }
}
