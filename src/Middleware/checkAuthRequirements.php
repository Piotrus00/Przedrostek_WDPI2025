<?php
require_once __DIR__ . '/../annotation/RequireLogin.php';
function checkAuthRequirements(object|string $controller, string $methodName): void
{
    $reflection = new \ReflectionMethod($controller, $methodName); # podglad metod klasy $controller

    // Pobieramy atrybuty typu RequireLogin
    $attributes = $reflection->getAttributes(RequireLogin::class);

    // Jeśli znaleziono atrybut RequireLogin
    if (!empty($attributes)) {
        $instance = $attributes[0]->newInstance(); // Tworzymy instancję atrybutu - dostep do prawdziwych danych
        $requiredRoles = $instance->roles ?? []; // Pobieramy wymagane role

        // Sprawdzamy czy użytkownik JEST zalogowany
        $isLoggedIn = !empty($_SESSION['user_id']);

        if (!$isLoggedIn) {
            throw new \Exception("Użytkownik nie jest zalogowany", 401);
        }

        if (!empty($requiredRoles)) {
            $userRole = $_SESSION['user_role'] ?? RequireLogin::ROLE_USER;
            $isAdmin = ($userRole === RequireLogin::ROLE_ADMIN);
            if (!$isAdmin && !in_array($userRole, $requiredRoles, true)) { # jezeli nie admin i nie ma wymaganej roli
                throw new \Exception("Brak uprawnień", 403);
            }
        }
    }
}