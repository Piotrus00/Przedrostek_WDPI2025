<?php

#Router mapuje URLe na odpowiednie kontrolery i akcje
require_once 'AppController.php';
class DashboardController extends AppController {
    #[RequireLogin] # wymagane logowanie
    public function roulette(): void # metoda renderująca widok roulette
    {
        $this->render('roulette');
    }

    #[RequireLogin]
    public function statistics(): void
    {
        $this->render('statistics');
    }

    #[RequireLogin]
    public function upgrades(): void
    {
        $this->render('upgrades');
    }

    #[RequireLogin]
    public function adminPanel(): void
    {
        $this->render('admin-panel');
    }

}