<?php

require_once 'AppController.php';


class SecurityController extends AppController {

    public function login() {
        // TODO get data from database

        //  $this->render("login", ["name"=> "Adrian"]);
        return $this->render("login");
    }

    public function register() {
        // TODO pobranie z formularza email i hasła
        // TODO insert do bazy danych
        // TODO zwrocenie informajci o pomyslnym zarejstrowaniu
        return $this->render("login", ["message" => "Zarejestrowano uytkownika"]);
    }
}