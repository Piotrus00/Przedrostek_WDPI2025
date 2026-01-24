<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';
class DashboardController extends AppController {
    #[RequireLogin]
    public function roulette(): void
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