<?php

require_once __DIR__ . "/src/patterns/Singleton.php";

class Database extends Singleton {
    private $username;
    private $password;
    private $host;
    private $database;
    private $connection;

    protected function __construct()
    {
        $this->loadConfig();
        $this->connection = null;
    }

    private function loadConfig(): void
    {
        $envPath = __DIR__ . '/.env';
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                [$name, $value] = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                if (!array_key_exists($name, $_ENV)) {
                    $_ENV[$name] = $value;
                }
                putenv($name . '=' . $value);
            }
        }

        $this->username = getenv('DB_USER') !== false ? getenv('DB_USER') : 'docker';
        $this->password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'docker';
        $this->host = getenv('DB_HOST') !== false ? getenv('DB_HOST') : 'db';
        $this->database = getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'db';
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

            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $this->connection;
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function disconnect(): void
    {
        $this->connection = null;
    }
}