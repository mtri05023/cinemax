<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/../../config/dbConfig.php';
require_once __DIR__ . '/../../config/permissionConfig.php';

//Cac file models
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Role.php';
require_once __DIR__ . '/../../models/Permission.php';
require_once __DIR__ . '/../../models/Role_permissions.php';
require_once __DIR__ . '/../../models/FoodCombo.php';
require_once __DIR__ . '/../../models/Movie.php';
require_once __DIR__ . '/../../models/Genre.php';
require_once __DIR__ . '/../../models/Show.php';
require_once __DIR__ . '/../../models/Promotion.php';
require_once __DIR__ . '/../../models/Cinema.php';
require_once __DIR__ . '/../../models/Hall.php';
require_once __DIR__ . '/../../models/Ticket.php';

require_once __DIR__ . '/../../models/Bill.php';

// Cac file services
require_once __DIR__ . '/../../services/RoleService.php';
require_once __DIR__ . '/../../services/PermissionService.php';
require_once __DIR__ . '/../../services/Role_permissionsService.php';
require_once __DIR__ . '/../../services/UserService.php';
require_once __DIR__ . '/../../services/FoodComboService.php';
require_once __DIR__ . '/../../services/AuthMiddleware.php';
require_once __DIR__ . '/../../services/MovieService.php';
require_once __DIR__ . '/../../services/GenreService.php';
require_once __DIR__ . '/../../services/ShowService.php';
require_once __DIR__ . '/../../services/CinemaService.php';
require_once __DIR__ . '/../../services/HallService.php';
require_once __DIR__ . '/../../services/PromotionService.php';
require_once __DIR__ . '/../../services/TicketService.php';
require_once __DIR__ . '/../../services/BillService.php';

// Cac file controllers
require_once __DIR__ . '/../../controllers/AdminController.php';
require_once __DIR__ . '/../../controllers/Role_permissionsController.php';
require_once __DIR__ . '/../../controllers/FoodComboController.php';
require_once __DIR__ . '/../../controllers/MovieController.php';
require_once __DIR__ . '/../../controllers/GenreController.php';
require_once __DIR__ . '/../../controllers/ShowController.php';
require_once __DIR__ . '/../../controllers/CinemaController.php';
require_once __DIR__ . '/../../controllers/HallController.php';
require_once __DIR__ . '/../../controllers/PromotionController.php';

session_start();


// Kiem tra dang nhap va phan quyen admin bang JWT
$authUser = AuthMiddleware::requireAdmin();

//Ket noi DB
$conn = getDBConnection();

//Khoi tao models
$userModel = new User($conn);
$roleModel = new Role($conn);
$permissionModel = new Permission($conn);
$rolePermissionModel = new Role_permissions($conn);
$foodComboModel = new FoodCombo($conn);
$movieModel = new Movie($conn);
$genreModel = new Genre($conn);
$showModel = new Show($conn);
$cinemaModel = new Cinema($conn);
$hallModel = new Hall($conn);
$promotionModel = new Promotion($conn);
$ticketModel = new Ticket($conn);

$billModel = new Bill($conn);

//Khoi tao services
$userService = new UserService($userModel);
$roleService = new RoleService($roleModel);
$permissionService = new PermissionService($permissionModel, $rolePermissionModel);
$rolePermissionsService = new Role_permissionsService($rolePermissionModel);
$foodComboService = new FoodComboService($foodComboModel);
$movieService = new MovieService($movieModel, $genreModel);
$genreService = new GenreService($genreModel);
$cinemaService = new CinemaService($cinemaModel);
$hallService = new HallService($hallModel);
$promotionService = new PromotionService($promotionModel);
$ticketService = new TicketService($ticketModel);
$showService = new ShowService($showModel, $ticketService);
$billService = new BillService($billModel, $ticketService);

//Khoi tao controllers
$adminController = new AdminController($userService, $roleService, $permissionService, $billService);
$rolePermissionsController = new Role_permissionsController($rolePermissionsService);
$foodComboController = new FoodComboController($foodComboService);
$movieController = new MovieController($movieService);
$showController = new ShowController($showService);
$genreController = new GenreController($genreService);
$cinemaController = new CinemaController($cinemaService);
$promotionController = new PromotionController($promotionService);
$hallController = new HallController($hallService);

// Danh sach cac page hop le
$allowedPages = [
    'users',
    'roles',
    'permissions',
    'dashboard',
    'movies',
    'genres',
    'cinemas',
    'halls',
    'seat_types',
    'seats',
    'shows',
    'bookings',
    'combos',
    'promotions',
    '404'
];

$page = $_GET['page'] ?? 'dashboard';
if (!in_array($page, $allowedPages, true)) {
    $page = '404';
}

$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;

// =========================
// USERS
// =========================
if ($page === 'users' && $action) {
    switch ($action) {
        case 'create':
            $adminController->createUser();
            exit;

        case 'update':
            $adminController->updateUser($_POST['user_id'] ?? 0);
            exit;

        case 'delete':
            $adminController->deleteUser($_GET['id'] ?? 0);
            exit;
    }
}

// =========================
// ROLES
// =========================
if ($page === 'roles' && $action) {
    switch ($action) {
        case 'create':
            $adminController->createRole();
            exit;

        case 'update':
            $adminController->updateRole($_POST['role_id'] ?? 0);
            exit;

        case 'delete':
            $adminController->deleteRole($_GET['id'] ?? 0);
            exit;
    }
}

// =========================
// PERMISSIONS
// =========================
if ($page === 'permissions' && $action) {
    switch ($action) {
        case 'create':
            $adminController->createPermission();
            exit;

        case 'update':
            $adminController->updatePermission($_POST['permission_id'] ?? 0);
            exit;

        case 'delete':
            $adminController->deletePermission($_GET['id'] ?? 0);
            exit;

        case 'saveRolePermissions':
            $rolePermissionsController->saveRolePermissions();
            exit;
    }
}

// =========================
// COMBOS
// =========================
if ($page === 'combos' && $action) {
    switch ($action) {
        case 'create':
            $foodComboController->createCombo();
            exit;

        case 'update':
            $foodComboController->updateCombo($_POST['combo_id'] ?? 0);
            exit;

        case 'delete':
            $foodComboController->deleteCombo($_GET['id'] ?? 0);
            exit;
    }
}

// =========================
// MOVIES
// dùng POST cho create/update/delete
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'movies' && $action) {
    switch ($action) {
        case 'create':
            $movieController->create();
            exit;

        case 'update':
            $movieId = $id ?? ($_POST['movie_id'] ?? 0);
            $movieController->update($movieId);
            exit;

        case 'delete':
            $movieController->delete($id ?? 0);
            exit;
    }
}

// =========================
// GENRES
// dùng POST cho create/delete
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $page === 'genres' && $action) {
    switch ($action) {
        case 'create':
            $genreController->create();
            exit;

        case 'delete':
            $genreController->delete($_GET['id'] ?? 0);
            exit;
    }
}

if ($page === 'shows' && $action) {
    switch ($action) {
        case 'create':
            $showController->createShow();
            exit;

        case 'update':
            $showId = $id ?? ($_POST['show_id'] ?? 0);
            $showController->updateShow($showId);
            exit;

        case 'delete':
            $showController->deleteShow($_GET['id'] ?? 0);
            exit;
    }
}

// =========================
// PROMOTIONS
// =========================
if ($page === 'promotions' && $action) {
    switch ($action) {
        case 'create':
            $promotionController->createPromotion();
            exit;

        case 'update':
            $promotionController->updatePromotion($_POST['promotion_id'] ?? 0);
            exit;

        case 'delete':
            $promotionController->deletePromotion($_GET['id'] ?? 0);
            exit;
        case 'end':
            $promotionController->endPromotion($_GET['id'] ?? 0);
            exit;
    }
}
// BOOKINGS
// =========================
if ($page === 'bookings' && $action) {
    switch ($action) {
        case 'confirm':
            $adminController->confirmPayment($_GET['id'] ?? 0);
            exit;

        case 'cancel':
            $adminController->cancelBill($_GET['id'] ?? 0);
            exit;
    }
}

$contentPath = __DIR__ . "/pages/$page.php";
?>


<!DOCTYPE html>
<html lang="vi">

<head>
    <?php include __DIR__ . '/partials/head.php'; ?>
</head>

<body>
    <div class="admin-layout">
        <?php include __DIR__ . '/partials/sidebar.php'; ?>

        <main class="admin-main">
            <?php include $contentPath; ?>
        </main>
    </div>
</body>

</html>

<!--
                    <?php echo "<pre>";
                    print_r($_SESSION);
                    print_r($authUser);
                    echo "</pre>"; ?>
        -->