<?php

require_once 'AppController.php';
require_once __DIR__.'/../repository/UserRepository.php';
require_once __DIR__.'/../repository/CardsRepository.php';
class DashboardController extends AppController {

    private $cardsRepository;
    public function __construct()
    {
        parent::__construct();
        $this->cardsRepository = new CardsRepository();
    }
    #[RequireLogin]
    public function index(){
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->render('dashboard');
    }

    public function search()
    {
        $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

        if($contentType !== 'application/json'){
            echo json_encode(['message' => 'it is not endpoit for this method']);
            return;
        }

        if(!$this->isPost()){
            echo json_encode(['message' => 'method not allowed']);
        }
        $content = trim(file_get_contents("php://input"));
        $decoded = json_decode($content, true);

        http_response_code(200);

        echo json_encode($this->cardsRepository->getCardsByTitle($decoded['search']));
        return;
    }

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