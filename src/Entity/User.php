<?php

namespace App\Entity;

class User
{
    private string $id;
    private string $name;
    private int $age;
    private string $gender;
    private array $interests;
    private string $location;

    public function __construct(
        string $id,
        string $name,
        int $age,
        string $gender,
        array $interests,
        string $location
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->age = $age;
        $this->gender = $gender;
        $this->interests = $interests;
        $this->location = $location;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function getGender(): string
    {
        return $this->gender;
    }

    public function getInterests(): array
    {
        return $this->interests;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'age' => $this->age,
            'gender' => $this->gender,
            'interests' => $this->interests,
            'location' => $this->location
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            $data['name'],
            $data['age'],
            $data['gender'],
            $data['interests'],
            $data['location']
        );
    }
}
