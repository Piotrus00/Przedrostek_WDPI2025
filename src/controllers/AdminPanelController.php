<?php

require_once 'AppController.php';

class AdminPanelController extends AppController
{
    public function __construct()
    {
        parent::__construct();
    }

    #[RequireLogin([RequireLogin::ROLE_ADMIN])]
    public function index(): void
    {
        $this->render('admin-panel');
    }
}
