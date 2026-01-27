<?php

namespace App\Models;

require_once __DIR__ . '/../repository/UserRepository.php';

use UserRepository;

class UserDefinition
{
    public function __construct(
        public int $id,
        public string $email,
        public string $password,
        public string $firstname,
        public string $lastname,
        public string $role,
        public int $balance
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            email: (string) ($data['email'] ?? ''),
            password: (string) ($data['password'] ?? ''),
            firstname: (string) ($data['firstname'] ?? ''),
            lastname: (string) ($data['lastname'] ?? ''),
            role: (string) ($data['role'] ?? 'user'),
            balance: (int) ($data['balance'] ?? 0)
        );
    }

    public static function findByEmail(string $email): ?self
    {
        $repository = new UserRepository();
        $row = $repository->getUserByEmail($email);
        if (!$row) {
            return null;
        }

        return self::fromArray($row);
    }

    # tworzenie uzytkownika
    public static function create(
        string $email,
        string $hashedPassword,
        string $firstName,
        string $lastName,
        string $role = 'user',
        int $balance = 1000
    ): void {
        $repository = new UserRepository(); // tworzenie instancji repozytorium
        $repository->createUser($email, $hashedPassword, $firstName, $lastName, $role, $balance); // wywołanie metody tworzącej użytkownika(repozytorium)
    }

    public static function getBalanceById(int $userId): int
    {
        $repository = new UserRepository();
        return $repository->getUserBalanceById($userId);
    }

    public static function updateBalance(int $userId, int $newBalance): void
    {
        $repository = new UserRepository(); // tworzenie instancji repozytorium
        $repository->updateUserBalance($userId, $newBalance); // wywołanie metody aktualizującej saldo użytkownika(repozytorium)
    }

    public static function getEnabledById(int $userId): bool
    {
        $repository = new UserRepository();
        return $repository->getUserEnabledById($userId);
    }

    public static function setEnabled(int $userId, bool $enabled): void
    {
        $repository = new UserRepository();
        $repository->updateUserEnabled($userId, $enabled);
    }

    # do _SESSION
    public function toSessionData(): array
    {
        return [
            'user_id' => $this->id,
            'user_email' => $this->email,
            'user_role' => $this->role,
            'user_balance' => $this->balance
        ];
    }
}
