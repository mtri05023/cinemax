<?php
/**
 * Router chính - xử lý các request cho auth (login, register, logout)
 * URL: /public/index.php?action=login|register|logout
 */


require_once __DIR__ . '/../config/dbConfig.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../controllers/AuthController.php';

// Khởi tạo dependencies
$conn = getDBConnection();
$userModel = new User($conn);
$authService = new AuthService($userModel);
$authController = new AuthController($authService);

// Lấy action từ query string
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->login();
        } else {
            header('Location: ../views/auth/login.php');
        }
        break;

    case 'register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->register();
        } else {
            header('Location: ../views/auth/register.php');
        }
        break;

    case 'logout':
        $authController->logout();
        break;

    default:
        // Mặc định redirect về trang user
        header('Location: ../views/user/index.php');
        break;
}
exit;

