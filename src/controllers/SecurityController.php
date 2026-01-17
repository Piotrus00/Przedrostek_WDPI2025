<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';
require_once __DIR__ . '/../annotation/AllowedMethods.php';
class SecurityController extends AppController {
    private $userRepository;

    public function __construct() {
        parent::__construct();
        $this->userRepository = new UserRepository();
    }

    #[AllowedMethods(['POST', 'GET'])]
    public function login() {

        if($this->isGet()) {
            return $this->render("login");
        }

        $email = $_POST["email"] ?? '';
        $password = $_POST["password"] ?? '';

        if(empty($email) || empty($password)) {
            return $this->render("login", ["message" => "Fill all fields"]);
        }

        $user = $this->userRepository->getUserByEmail($email);

        if(!$user){
            return $this->render("login", ["message" => "User not found"]);

        }
       if(!password_verify($password, $user['password'])){
           return $this->render("login", ["message" => "Wrong password"]);
       }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];

        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/dashboard");
    }
    #[AllowedMethods(['POST', 'GET'])]
    public function register() {

        if ($this->isGet()) {
            return $this->render("register");
        }

        $email = $_POST["email"] ?? '';
        $password1 = $_POST["password1"] ?? '';
        $password2 = $_POST["password2"] ?? '';
        $firstname = $_POST["firstname"] ?? '';
        $lastname = $_POST["lastname"] ?? '';

        if (empty($email) || empty($password1) || empty($firstname) ||  empty($password2) || empty($lastname)) {
            return $this->render('register', ['messages' => ['Fill all fields']]);
        }

        if ($password1 !== $password2) {
            return $this->render('register', ['messages' => ['Passwords should be the same!']]);
        }

        $existingUser = $this->userRepository->getUserByEmail($email);

        if ($existingUser) {
            return $this->render('register', ['messages' => ['User with this email already exists!']]);
        }

        $hashedPassword = password_hash($password1, PASSWORD_BCRYPT);

        $this->userRepository->createUser(
            $email,
            $hashedPassword,
            $firstname,
            $lastname
        );

        return $this->render("login", ["message" => "Zarejestrowano uytkownika ".$email]);
    }

    public function logout()
    {
        // upewniamy się, że sesja jest uruchomiona
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // czyścimy wszystkie dane sesji
        $_SESSION = [];

        // opcjonalnie, kasujemy ciasteczko sesji po stronie przeglądarki
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

        // niszczymy sesję
        session_destroy();

        // przekierowanie np. na ekran logowania
        $url = "http://$_SERVER[HTTP_HOST]";
        header("Location: {$url}/login");
    }
}