<?php

require_once 'Repository.php';

class UserRepository extends Repository
{

    public function getUser(): ?array
    {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM "users" 
        ');
        $stmt->execute();

        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $users;
    }

    #transkacja
    public function createUser(string $email, string $hashedPassword, string $firstName, string $lastName, string $role = 'user', int $balance = 1000 ): void{
        $connection = $this->database->connect();
        try {
            $connection->beginTransaction();

            $stmt = $connection->prepare('
            INSERT INTO users (email, password, firstname, lastname, balance, role) VALUES (?,?,?,?,?,?);
            ');
            $stmt->execute([$email, $hashedPassword, $firstName, $lastName, $balance, $role]);
            // Insert odpowiedzialny za tworzenie użytkownika (SecurityController::register)

            $connection->commit();
        } catch (Throwable $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $e;
        }
    }

    public function getUserByEmail(string $email) {
        $stmt = $this->database->connect()->prepare('
            SELECT * FROM users WHERE email = :email
        ');
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $users = $stmt->fetch(PDO::FETCH_ASSOC);

        return $users;
    }

    public function getUserBalanceById(int $userId): int
    {
        $stmt = $this->database->connect()->prepare('
            SELECT balance FROM users WHERE id = :id
        ');
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return 0;
        }

        return (int) $row['balance'];
    }

    public function updateUserBalance(int $userId, int $newBalance): void
    {
        $stmt = $this->database->connect()->prepare('
            UPDATE users SET balance = :balance WHERE id = :id
        ');
        $stmt->bindParam(':balance', $newBalance, PDO::PARAM_INT);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }
}