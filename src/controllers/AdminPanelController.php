<?php

require_once 'AppController.php';
require_once __DIR__ . '/../annotation/AllowedMethods.php';
require_once __DIR__ . '/../annotation/RequireLogin.php';
require_once __DIR__ . '/../repository/AdminRepository.php';

class AdminPanelController extends AppController
{
    private AdminRepository $adminRepository;

    public function __construct()
    {
        parent::__construct();
        $this->adminRepository = new AdminRepository();
    }

    #[RequireLogin([RequireLogin::ROLE_ADMIN])]
    public function index(): void
    {
        $this->render('admin-panel', [
            'csrfToken' => $this->getCsrfToken()
        ]);
    }

    #[AllowedMethods(['GET', 'POST'])]
    #[RequireLogin([RequireLogin::ROLE_ADMIN])]
    public function adminApi(): void
    {
        header('Content-Type: application/json');

        if ($this->isGet()) {
            echo json_encode([
                'success' => true,
                'users' => $this->adminRepository->getUsers(),
                'loginAttemptStats' => $this->adminRepository->getLoginAttemptStats(50)
            ]);
            return;
        }

        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
            return;
        }

        $csrfToken = $input['csrf'] ?? '';
        if (!$this->verifyCsrfToken($csrfToken)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            return;
        }

        $action = $input['action'] ?? '';
        if ($action === 'delete-user') {
            $userId = (int) ($input['userId'] ?? 0);
            if ($userId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid user id']);
                return;
            }

            if (!empty($_SESSION['user_id']) && (int) $_SESSION['user_id'] === $userId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Cannot delete current user']);
                return;
            }

            $this->adminRepository->deleteUser($userId);
            echo json_encode(['success' => true]);
            return;
        }

        if ($action === 'set-enabled') {
            $userId = (int) ($input['userId'] ?? 0);
            $enabled = isset($input['enabled']) ? (bool) $input['enabled'] : null;
            if ($userId <= 0 || $enabled === null) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid payload']);
                return;
            }

            if (!empty($_SESSION['user_id']) && (int) $_SESSION['user_id'] === $userId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Cannot change current user']);
                return;
            }

            $this->adminRepository->setUserEnabled($userId, $enabled);
            echo json_encode(['success' => true]);
            return;
        }

        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
}
