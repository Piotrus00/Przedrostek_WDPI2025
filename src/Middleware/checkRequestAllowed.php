<?php
require_once __DIR__ . '/../annotation/AllowedMethods.php';

function checkRequestAllowed(object $controller, string $methodName) {
    $reflection = new ReflectionMethod($controller, $methodName);
    $attributes  = $reflection->getAttributes(AllowedMethods::class);

    if (!empty($attributes)) {
        $instance = $attributes[0]->newInstance();
        $allowed = $instance->methods;

        if (!in_array($_SERVER['REQUEST_METHOD'], $allowed)) {
            header('Allow: ' . implode(', ', $allowed));

            // Rzucamy wyjątek z kodem 405 (Method Not Allowed)
            throw new \Exception("Method Not Allowed", 405);
        }
    }
}
