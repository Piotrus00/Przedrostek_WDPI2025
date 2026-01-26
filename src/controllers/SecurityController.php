<?php

require_once 'AppController.php';
require_once __DIR__ . '/../models/UserDefinition.php';
require_once __DIR__ . '/../annotation/AllowedMethods.php';

use App\Models\UserDefinition;
class SecurityController extends AppController {

    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOGIN_BLOCK_SECONDS = 3600;
    private const PASSWORD_MIN_LENGTH = 8;
    private const PASSWORD_MAX_LENGTH = 64;
    private const GENERIC_INVALID_MESSAGE = 'Invalid Password or Email';

    private function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function isValidPassword(string $password): bool
    {
        if (strlen($password) < self::PASSWORD_MIN_LENGTH || strlen($password) > self::PASSWORD_MAX_LENGTH) {
            return false;
        }

        $hasNumber = (bool) preg_match('/\d/', $password);
        $hasSymbol = (bool) preg_match('/[^a-zA-Z0-9]/', $password);

        return $hasNumber && $hasSymbol;
    }

    private function getLoginAttemptKey(string $email): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return strtolower(trim($email)) . '|' . $ip;
    }

    private function getLoginAttemptData(string $key): array
    {
        if (!isset($_SESSION['login_attempts'][$key])) {
            return ['count' => 0, 'blocked_until' => 0];
        }

        return $_SESSION['login_attempts'][$key];
    }

    private function setLoginAttemptData(string $key, array $data): void
    {
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }
        $_SESSION['login_attempts'][$key] = $data;
    }

    private function isLoginBlocked(string $key): bool
    {
        $data = $this->getLoginAttemptData($key);
        return !empty($data['blocked_until']) && $data['blocked_until'] > time();
    }

    private function recordFailedLogin(string $key): void
    {
        $data = $this->getLoginAttemptData($key);
        $count = (int) ($data['count'] ?? 0) + 1;
        $blockedUntil = (int) ($data['blocked_until'] ?? 0);

        if ($count >= self::MAX_LOGIN_ATTEMPTS) {
            $blockedUntil = time() + self::LOGIN_BLOCK_SECONDS;
            $count = 0;
        }

        $this->setLoginAttemptData($key, [
            'count' => $count,
            'blocked_until' => $blockedUntil
        ]);
    }

    private function clearFailedLogins(string $key): void
    {
        if (isset($_SESSION['login_attempts'][$key])) {
            unset($_SESSION['login_attempts'][$key]);
        }
    }

    #[AllowedMethods(['POST', 'GET'])]
    public function login() {

        if($this->isGet()) {
            return $this->render("login", [
                'csrfToken' => $this->getCsrfToken()
            ]);
        }

        $email = $_POST["email"] ?? '';
        $password = $_POST["password"] ?? '';
        $csrfToken = $_POST['csrf_token'] ?? '';

        if (!$this->verifyCsrfToken($csrfToken)) {
            return $this->render("login", [
                "messages" => ["Invalid request"],
                'csrfToken' => $this->getCsrfToken()
            ]);
        }

        $attemptKey = $this->getLoginAttemptKey($email);
        if ($this->isLoginBlocked($attemptKey)) {
            return $this->render("login", [
                "messages" => ["Too many login attempts. Try again later."],
                'csrfToken' => $this->getCsrfToken()
            ]);
        }

        if(empty($email) || empty($password)) {
            $this->recordFailedLogin($attemptKey);
            return $this->render("login", [
                "messages" => [self::GENERIC_INVALID_MESSAGE],
                'csrfToken' => $this->getCsrfToken()
            ]);
        }

        if (!$this->isValidEmail($email)) {
            $this->recordFailedLogin($attemptKey);
            return $this->render("login", [
                "messages" => [self::GENERIC_INVALID_MESSAGE],
                'csrfToken' => $this->getCsrfToken()
            ]);
        }

        $user = UserDefinition::findByEmail($email); // szukamy uytkownika o podanym emailu

        if(!$user){
            $this->recordFailedLogin($attemptKey);
            return $this->render("login", [
                "messages" => [self::GENERIC_INVALID_MESSAGE],
                'csrfToken' => $this->getCsrfToken()
            ]); // nie znaleziono uytkownika

        }
       if(!password_verify($password, $user->password)){
           $this->recordFailedLogin($attemptKey);
           return $this->render("login", [
               "messages" => [self::GENERIC_INVALID_MESSAGE],
               'csrfToken' => $this->getCsrfToken()
           ]); // niepoprawne hasło
       }

        $_SESSION = array_merge($_SESSION, $user->toSessionData()); // zapisujemy dane uytkownika w sesji
        $this->clearFailedLogins($attemptKey);

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/roulette"); // zakładamy, że dashboard to strona po zalogowaniu
    }
    #[AllowedMethods(['POST', 'GET'])]
    public function register() {

        if ($this->isGet()) {
            return $this->render("register", [
                'csrfToken' => $this->getCsrfToken()
            ]);
        }

        $email = $_POST["email"] ?? '';
        $password1 = $_POST["password1"] ?? '';
        $password2 = $_POST["password2"] ?? '';
        $firstname = $_POST["firstname"] ?? '';
        $lastname = $_POST["lastname"] ?? '';
        $csrfToken = $_POST['csrf_token'] ?? '';

        if (!$this->verifyCsrfToken($csrfToken)) {
            return $this->render("register", [
                'messages' => ['Invalid request'],
                'csrfToken' => $this->getCsrfToken()
            ]);
        }

        if (empty($email) || empty($password1) || empty($firstname) ||  empty($password2) || empty($lastname)) {
            return $this->render('register', [
                'messages' => ['Fill all fields'],
                'csrfToken' => $this->getCsrfToken()
            ]);
        }

        if ($password1 !== $password2) {
            return $this->render('register', [
                'messages' => ['Passwords should be the same!'],
                'csrfToken' => $this->getCsrfToken()
            ]);
        }

        if (!$this->isValidEmail($email)) {
            return $this->render('register', [
                'messages' => [self::GENERIC_INVALID_MESSAGE],
                'csrfToken' => $this->getCsrfToken()
            ]);
        }

        if (!$this->isValidPassword($password1)) {
            return $this->render('register', [
                'messages' => ['Password must be at least 8 characters and include 1 number and 1 symbol.'],
                'csrfToken' => $this->getCsrfToken()
            ]);
        }

        $existingUser = UserDefinition::findByEmail($email); // sprawdzamy, czy uzytkownik juz istnieje

        if ($existingUser) {
            return $this->render('register', [
                'messages' => [self::GENERIC_INVALID_MESSAGE],
                'csrfToken' => $this->getCsrfToken()
            ]);
        }

        $hashedPassword = password_hash($password1, PASSWORD_BCRYPT); // hashowanie hasła BCRYPT

        $initialBalance = 1000; // ustalamy początkowy balans dla nowego uzytkownika
        $default_role = 'user';

        # tworzymy nowego uzytkownika przez userDefinition(model) 
        UserDefinition::create(
            $email,
            $hashedPassword,
            $firstname,
            $lastname,
            $default_role,
            $initialBalance
        );

        return $this->render("login", [
            "messages" => ["Zarejestrowano uzytkownika " . $email],
            'csrfToken' => $this->getCsrfToken()
        ]);
    }

    public function logout()
    {
        // upewniamy się, że sesja jest uruchomiona
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // czyścimy wszystkie dane sesji
        $_SESSION = [];

        // kasujemy ciasteczko sesji po stronie przeglądarki
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // niszczymy sesję po stronie serwera
        session_destroy();

        // przekierowanie np. na ekran logowania
        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/login");
    }
}