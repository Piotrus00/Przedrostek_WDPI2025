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
        $templatePath = 'public/views/' . $template . '.html';
        $output = 'public/views/404.html';

        if (file_exists($templatePath)) {
            extract($variables);

            ob_start();
            include $templatePath;
            $output = ob_get_clean();
        } else{
            ob_start();
            include $templatePath;
            $output = ob_get_clean();
        }
        echo $output;
    }

}