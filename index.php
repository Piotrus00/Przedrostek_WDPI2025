<?php

session_start();
require 'Routing.php';

try {
    $path = trim($_SERVER['REQUEST_URI'], '/'); # /localhost:8000/login -> localhost:8000/login
    $path = parse_url($path, PHP_URL_PATH); # localhost:8000/login -> login

    Routing::run($path);
}
catch (\Exception $e) {
    if ($e->getCode() === 405) {
        http_response_code(405);
        echo "<h1>Błąd 405</h1>";
        echo "<p>Niedozwolona metoda żądania. " . htmlspecialchars($e->getMessage()) . "</p>";
        exit;
    }
    if ($e->getCode() === 401) {
        http_response_code(401);
        include 'public/views/401.html';
        exit;
    }
    if ($e->getCode() === 403) {
        http_response_code(403); // brak uprawnień
        include 'public/views/403.html';
        exit;
    }
    if ($e->getCode() === 400) {
        http_response_code(400);
        include 'public/views/400.html';
        exit;
    }

    http_response_code(500);
        include 'public/views/500.html';
        exit;
    }
    ?>