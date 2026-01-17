<?php

require_once "config.php";
require_once __DIR__ . "/src/patterns/Singleton.php";

class Database extends Singleton {
    private $username;
    private $password;
    private $host;
    private $database;
    private $connection;

    protected function __construct()
    {
        $this->username = USERNAME;
        $this->password = PASSWORD;
        $this->host = HOST;
        $this->database = DATABASE;
        $this->connection = null;
    }

    public function connect()
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        try {
            $this->connection = new PDO(
                "pgsql:host=$this->host;port=5432;dbname=$this->database",
                $this->username,
                $this->password,
                ["sslmode"  => "prefer"]
            );

            // set the PDO error mode to exception
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->connection;
        }
        catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function disconnect(): void
    {
        $this->connection = null;
    }
}