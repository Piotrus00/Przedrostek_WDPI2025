<?php
require_once __DIR__ . '/../annotation/AllowedMethods.php';

function checkRequestAllowed(object $controller, string $methodName) {
    $reflection = new ReflectionMethod($controller, $methodName); # podglad metod klasy $controller
    $attributes  = $reflection->getAttributes(AllowedMethods::class); # pobieramy atrybuty typu AllowedMethods

    if (!empty($attributes)) {
        $instance = $attributes[0]->newInstance(); // Tworzymy instancję atrybutu - dostep do prawdziwych danych
        $allowed = $instance->methods; // Pobieramy dozwolone metody

        if (!in_array($_SERVER['REQUEST_METHOD'], $allowed)) { // Sprawdzamy czy metoda jest dozwolona
            header('Allow: ' . implode(', ', $allowed)); // wysyłamy nagłówek z dozwolonymi metodami

            throw new \Exception("Method Not Allowed", 405);
        }
    }
}
