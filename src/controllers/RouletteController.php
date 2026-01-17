<?php

require_once 'AppController.php';

class RouletteController extends AppController
{
    public function __construct()
    {
        parent::__construct();
    }

    #[RequireLogin]
    public function index(): void
    {
        $this->render('roulette');
    }
}

