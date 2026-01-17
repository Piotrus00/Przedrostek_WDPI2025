<?php
require_once __DIR__ . '/../annotation/RequireLogin.php';
function checkAuthRequirements(object|string $controller, string $methodName): void
{
    $reflection = new \ReflectionMethod($controller, $methodName);

    // Pobieramy atrybuty typu RequireLogin
    $attributes = $reflection->getAttributes(RequireLogin::class);

    // Jeśli znaleziono atrybut RequireLogin
    if (!empty($attributes)) {
        $instance = $attributes[0]->newInstance();
        $requiredRoles = $instance->roles ?? [];

        // Sprawdzamy czy użytkownik JEST zalogowany
        // (Tutaj wstaw swój warunek, np. sprawdzenie sesji)
        $isLoggedIn = !empty($_SESSION['user_id']);

        if (!$isLoggedIn) {
            // Rzucamy wyjątek 401 (Unauthorized) zamiast die()
            // To pozwoli Ci przekierować użytkownika na login w index.php
            throw new \Exception("Użytkownik nie jest zalogowany", 401);
        }

        if (!empty($requiredRoles)) {
            $userRole = $_SESSION['user_role'] ?? RequireLogin::ROLE_USER;
            $isAdmin = $userRole === RequireLogin::ROLE_ADMIN;
            if (!$isAdmin && !in_array($userRole, $requiredRoles, true)) {
                throw new \Exception("Brak uprawnień", 403);
            }
        }
    }
}