<?php

require_once 'AppController.php';
require_once __DIR__ . '/../models/UserDefinition.php';
require_once __DIR__ . '/../annotation/AllowedMethods.php';
require_once __DIR__ . '/../repository/LoginAttemptsRepository.php';

use App\Models\UserDefinition;
class SecurityController extends AppController {

    private LoginAttemptsRepository $loginAttemptsRepository;

    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOGIN_BLOCK_SECONDS = 3600;
    private const PASSWORD_MIN_LENGTH = 8;
    private const PASSWORD_MAX_LENGTH = 64;
    private const GENERIC_INVALID_MESSAGE = 'Invalid Password or Email'; # Nie zdradzam, cz email istnieje...

    public function __construct()
    {
        parent::__construct();
        $this->loginAttemptsRepository = new LoginAttemptsRepository();
    }

    #validacja formatu emaila po stronie serwera
    private function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    #validacja zlozonosci hasła
    private function isValidPassword(string $password): bool
    {
        if (strlen($password) < self::PASSWORD_MIN_LENGTH || strlen($password) > self::PASSWORD_MAX_LENGTH) {
            return false;
        }

        $hasNumber = (bool) preg_match('/\d/', $password); // przynajmniej jedna cyfra
        $hasSymbol = (bool) preg_match('/[^a-zA-Z0-9]/', $password); // przynajmniej jeden znak specjalny

        return $hasNumber && $hasSymbol;
    }

    # limit prob logowania
    private function getClientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    private function isLoginBlocked(string $email, string $ipAddress): bool
    {
        $blockedUntil = $this->loginAttemptsRepository->getActiveBlock($email, $ipAddress);
        if (!$blockedUntil) {
            return false;
        }

        return strtotime($blockedUntil) > time();
    }

    private function recordFailedLogin(string $email, string $ipAddress): ?string
    {
        $recentFailures = $this->loginAttemptsRepository->countRecentFailures(
            $email,
            $ipAddress,
            self::LOGIN_BLOCK_SECONDS
        );

        $shouldBlock = ($recentFailures + 1) >= self::MAX_LOGIN_ATTEMPTS;
        $blockedUntil = $shouldBlock ? date('Y-m-d H:i:s', time() + self::LOGIN_BLOCK_SECONDS) : null;

        $this->loginAttemptsRepository->logAttempt($email, $ipAddress, false, $blockedUntil);

        return $blockedUntil;
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
        $emailNormalized = strtolower(trim($email));
        $ipAddress = $this->getClientIp();

        if (!$this->verifyCsrfToken($csrfToken)) {
            return $this->render("login", [
                "messages" => ["Invalid request"],
                'csrfToken' => $this->getCsrfToken()
            ]);
        }

        if ($this->isLoginBlocked($emailNormalized, $ipAddress)) {
            return $this->render("login", [
                "messages" => ["Too many login attempts. Try again later."],
                'csrfToken' => $this->getCsrfToken()
            ]);
        }

        if(empty($email) || empty($password)) {
            $blockedUntil = $this->recordFailedLogin($emailNormalized, $ipAddress);
            if ($blockedUntil) {
                return $this->render("login", [
                    "messages" => ["Too many login attempts. Try again later."],
                    'csrfToken' => $this->getCsrfToken()
                ]);
            }
            return $this->render("login", [
                "messages" => [self::GENERIC_INVALID_MESSAGE],
                'csrfToken' => $this->getCsrfToken()
            ]);
        }

        if (!$this->isValidEmail($email)) {
            $blockedUntil = $this->recordFailedLogin($emailNormalized, $ipAddress);
            if ($blockedUntil) {
                return $this->render("login", [
                    "messages" => ["Too many login attempts. Try again later."],
                    'csrfToken' => $this->getCsrfToken()
                ]);
            }
            return $this->render("login", [
                "messages" => [self::GENERIC_INVALID_MESSAGE],
                'csrfToken' => $this->getCsrfToken()
            ]);
        }

        $user = UserDefinition::findByEmail($email); // szukamy uytkownika o podanym emailu

        if(!$user){
            $blockedUntil = $this->recordFailedLogin($emailNormalized, $ipAddress);
            if ($blockedUntil) {
                return $this->render("login", [
                    "messages" => ["Too many login attempts. Try again later."],
                    'csrfToken' => $this->getCsrfToken()
                ]);
            }
            return $this->render("login", [
                "messages" => [self::GENERIC_INVALID_MESSAGE],
                'csrfToken' => $this->getCsrfToken()
            ]); // nie znaleziono uytkownika

        }
       if(!password_verify($password, $user->password)){
           $blockedUntil = $this->recordFailedLogin($emailNormalized, $ipAddress);
           if ($blockedUntil) {
               return $this->render("login", [
                   "messages" => ["Too many login attempts. Try again later."],
                   'csrfToken' => $this->getCsrfToken()
               ]);
           }
           return $this->render("login", [
               "messages" => [self::GENERIC_INVALID_MESSAGE],
               'csrfToken' => $this->getCsrfToken()
           ]); // niepoprawne hasło
       }

        $_SESSION = array_merge($_SESSION, $user->toSessionData()); // zapisujemy dane uytkownika w sesji
        $this->loginAttemptsRepository->logAttempt($emailNormalized, $ipAddress, true, null); // logujemy udane logowanie

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