<?php

require_once 'Repository.php';
//require_once __DIR__.'/../models/User.php';

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

    public function createUser(string $email, string $hashedPassword, string $firstName, string $lastName ): void{
        $stmt = $this->database->connect()->prepare('
        INSERT INTO users (email, password, firstname, lastname) VALUES (?,?,?,?);
        ');
        $stmt->execute([$email, $hashedPassword, $firstName, $lastName]);
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
}