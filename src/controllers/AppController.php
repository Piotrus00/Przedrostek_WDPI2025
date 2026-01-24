<?php
require_once 'src/patterns/Singleton.php';
class AppController extends Singleton
{

    protected function isGet(): bool
    {
        return $_SERVER["REQUEST_METHOD"] === 'GET';
    }

    protected function isPost(): bool
    {
        return $_SERVER["REQUEST_METHOD"] === 'POST';
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

}