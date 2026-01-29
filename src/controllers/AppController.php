<?php
require_once 'src/patterns/Singleton.php';
class AppController extends Singleton
{

    protected function isGet(): bool
    {
        return $_SERVER["REQUEST_METHOD"] === 'GET';
    }

    protected function render(string $template = null, array $variables = []) 
    {
        $templatePath = 'public/views/' . $template . '.html'; # public/views/login.html
        $output = 'public/views/404.html'; # domyślnie 404

        # sprawdza czy plik istnieje
        if (file_exists($templatePath)) {
            extract($variables); # zmienne z tablicy jako zmienne lokalne

            # bufor - zamiast natychmiast wysyłać HTML do przeglądarki najpierw go przechowuje w zmiennej
            ob_start();
            include $templatePath;
            $output = ob_get_clean();
        } else{
            ob_start();
            $output = 'public/views/404.html';
            $output = ob_get_clean();
        }
        echo $output;
    }

    protected function getCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf'];
    }

    protected function verifyCsrfToken(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf']) || empty($token)) {
            return false;
        }

        return hash_equals($_SESSION['csrf'], $token);
    }

}