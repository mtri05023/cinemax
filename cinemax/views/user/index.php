<?php
//Ket noi db
require_once __DIR__ . '/../../config/dbConfig.php';

//Cac file models
require_once __DIR__ . '/../../models/Movie.php';
require_once __DIR__ . '/../../models/Genre.php';
require_once __DIR__ . '/../../models/Promotion.php';
require_once __DIR__ . '/../../models/Show.php';
require_once __DIR__ . '/../../models/Ticket.php';
require_once __DIR__ . '/../../models/Bill.php';
require_once __DIR__ . '/../../models/User.php';



//Cac file service
require_once __DIR__ . '/../../services/AuthMiddleware.php';
require_once __DIR__ . '/../../services/MovieService.php';
require_once __DIR__ . '/../../services/PromotionService.php';
require_once __DIR__ . '/../../services/ShowService.php';
require_once __DIR__ . '/../../services/TicketService.php';
require_once __DIR__ . '/../../services/BillService.php';
require_once __DIR__ . '/../../services/UserService.php';
//Cac file controller
require_once __DIR__ . '/../../controllers/ShowController.php';
require_once __DIR__ . '/../../controllers/UserController.php';

session_start();


// Lấy thông tin user từ JWT (null nếu chưa đăng nhập - user trang chủ không bắt buộc login)
$authUser = AuthMiddleware::getAuthUser();

//Ket noi db
$conn = getDbConnection();

//Khoi tao models
$movieModel = new Movie($conn);
$genreModel = new Genre($conn);
$promotionModel = new Promotion($conn);
$showModel = new Show($conn);
$ticketModel = new Ticket($conn);
$billModel = new Bill($conn);
$userModel = new User($conn);

//Khoi tao services
$promotionService = new PromotionService($promotionModel);
$movieService = new MovieService($movieModel, $genreModel);
$ticketService = new TicketService($ticketModel);
$showService = new ShowService($showModel, $ticketService);
$billService = new BillService($billModel, $ticketService);
$userService = new UserService($userModel);



//Khoi tao controller
$showController = new ShowController($showService);
$userController = new UserController($userService, $billService);




// Danh sách page hợp lệ (tránh hack ?content=../../)
$allowedPages = [
    'home',
    'movie_detail',
    'theaters',
    'movies',
    'search_results',
    'promotions',
    'showtimes',
    'seat_selection',
    'food_selection',
    'payment',
    'booking_success',
    'my_bookings',
    '404'
];

$page = $_GET['page'] ?? 'home';
$action = $_GET['action'] ?? null;

if (!in_array($page, $allowedPages, true)) {
    $page = '404';
}

require_once __DIR__.'/../../controllers/CheckoutController.php';
$checkoutService=new CheckoutService($conn,$promotionService);
$checkoutController=new CheckoutController($checkoutService,$conn);
if ($action==='seat_state') {
    header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
    $checkoutService->expire();
    $rows=$ticketService->getTicketByShowId((int)($_GET['show_id']??0));
    echo json_encode(array_map(fn($row)=>['ticket_id'=>(int)$row['ticket_id'],'status'=>$row['status']],$rows)); exit;
}
$checkoutHold=$checkoutController->handle($page,$action,$authUser);
$contentPath = __DIR__ . "/pages/$page.php";
?>

<head>
    <?php include __DIR__ . '/partials/head.php'; ?>
</head>

<body>
    <?php include __DIR__ . '/partials/header.php'; ?>

    <main>
        <?php include $contentPath; ?>
    </main>

    <?php include __DIR__ . '/partials/footer.php'; ?>
</body>

</html>
