<?php
session_start();
require 'Routing.php';
try {
    $path = trim($_SERVER['REQUEST_URI'], '/');
    $path = parse_url($path, PHP_URL_PATH);

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
        // Obsługa błędu braku logowania
        header("Location: /login"); // Przekierowanie do logowania
        var_dump($e->getMessage());
        exit;
    }
    if ($e->getCode() === 403) {
        http_response_code(403);
        include 'public/views/403.html';
        exit;
    }

    http_response_code(500);
    echo "Wystąpił błąd serwera.";
}
    ?>