<?php

/**
 * File test API cho HallController, SeatTypeController
 * 
 * Cách sử dụng:
 * - GET: test_api.php?controller=halls&action=getAll
 * - GET: test_api.php?controller=halls&action=getById&id=1
 * - POST: test_api.php?controller=halls&action=create (với POST data)
 */

// Tắt hiển thị lỗi để tránh output HTML trước JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Bắt đầu output buffering để bắt mọi output không mong muốn
ob_start();

// Bắt đầu session và set header ngay
session_start();
header('Content-Type: application/json; charset=utf-8');

// Xử lý lỗi
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (error_reporting() === 0) {
        return false;
    }
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}, E_ALL);
try {
    require_once __DIR__ . '/../../../config/dbConfig.php';
    require_once __DIR__ . '/../../../models/Hall.php';
    require_once __DIR__ . '/../../../models/SeatType.php';
    require_once __DIR__ . '/../../../models/Cinema.php';
    require_once __DIR__ . '/../../../models/Seat.php';
    require_once __DIR__ . '/../../../models/Show.php';
    require_once __DIR__ . '/../../../services/HallService.php';
    require_once __DIR__ . '/../../../services/SeatTypeService.php';
    require_once __DIR__ . '/../../../services/CinemaService.php';
    require_once __DIR__ . '/../../../services/SeatService.php';
    require_once __DIR__ . '/../../../controllers/HallController.php';
    require_once __DIR__ . '/../../../controllers/SeatTypeController.php';
    require_once __DIR__ . '/../../../controllers/CinemaController.php';
    require_once __DIR__ . '/../../../controllers/SeatController.php';
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'data' => null,
        'message' => 'Lỗi khi load files: ' . $e->getMessage(),
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
} catch (Error $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'data' => null,
        'message' => 'Lỗi khi load files: ' . $e->getMessage(),
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Xóa output buffer nếu có output không mong muốn
ob_end_clean();

// Khởi tạo kết nối và controllers
try {
    $conn = getDBConnection();

    $hallModel = new Hall($conn);
    $seatTypeModel = new SeatType($conn);
    $cinemaModel = new Cinema($conn);
    $seatModel = new Seat($conn);
    $showModel = new Show($conn);

    $hallService = new HallService($hallModel);
    $seatTypeService = new SeatTypeService($seatTypeModel);
    $cinemaService = new CinemaService($cinemaModel);
    $seatService = new SeatService($seatModel);

    $hallController = new HallController($hallService);
    $seatTypeController = new SeatTypeController($seatTypeService);
    $cinemaController = new CinemaController($cinemaService);
    $seatController = new SeatController($seatService);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'data' => null,
        'message' => 'Lỗi khi khởi tạo: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Lấy tham số
$controller = $_GET['controller'] ?? '';
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if ($controller === '' && in_array($action, ['getShowtimes', 'getAllForUser'], true)) {
    $controller = 'shows';
}

$response = [
    'success' => false,
    'data' => null,
    'message' => '',
    'error' => null
];

try {
    // Xử lý theo controller
    if ($method==='POST') {
        require_once __DIR__.'/../../../services/AuthMiddleware.php';
        require_once __DIR__.'/../../../config/permissionConfig.php';
        $apiUser=AuthMiddleware::getAuthUser();
        $module=['theaters'=>'cinemas','seattypes'=>'seat_types','showtimes'=>'shows'][$controller]??$controller;
        $operation=['reopen'=>'update','createBulk'=>'create','deleteAll'=>'delete'][$action]??$action;
        if (!$apiUser || !hasPermission($module.'_'.$operation)) throw new RuntimeException('Bạn không có quyền thực hiện thao tác này.');
        if ($module==='seats') {
            $managedHallId=(int)($_POST['hall_id']??$_GET['hall_id']??0);
            if (!$managedHallId && !empty($_GET['id'])) {
                $seat=$seatService->getSeatById((int)$_GET['id']);
                $managedHallId=(int)($seat['HallID']??$seat['hall_id']??0);
            }
            $managedHall=$hallService->getHallById($managedHallId);
            if (!$managedHall || (int)$managedHall['status']!==1 || (int)$managedHall['CinemaStatus']!==1) throw new RuntimeException('Phòng hoặc rạp đã ngừng hoạt động. Không thể quản lý ghế.');
        }
    }
    if ($action==='reopen') {
        if ($method!=='POST') throw new RuntimeException('Mở lại phải dùng POST.');
        $id=(int)($_GET['id']??0);
        if ($id<=0) throw new RuntimeException('ID không hợp lệ.');
        if ($controller==='cinemas') $cinemaService->reopenCinema($id);
        elseif ($controller==='halls') $hallService->reopenHall($id);
        else throw new RuntimeException('Thao tác không hợp lệ.');
        echo json_encode(['success'=>true,'data'=>null,'message'=>'Mở lại thành công.'],JSON_UNESCAPED_UNICODE);
        exit;
    }
    switch ($controller) {
        case 'halls':
            handleHallRequests($hallController, $action, $method, $response, $hallService);
            break;

        case 'seat_types':
        case 'seattypes':
            handleSeatTypeRequests($seatTypeController, $action, $method, $response, $seatTypeService);
            break;

        case 'cinemas':
        case 'theaters':
            handleCinemaRequests($cinemaController, $action, $method, $response, $cinemaService);
            break;

        case 'seats':
            handleSeatRequests($seatController, $action, $method, $response, $seatService);
            break;
        case 'shows':
        case 'showtimes':
            handleShowRequests($showModel, $action, $method, $response);
            break;

        default:
            // Hiển thị danh sách API có sẵn
            showApiList();
            break;
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
    $response['message'] = 'Có lỗi xảy ra: ' . $e->getMessage();
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
} catch (Error $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
    $response['message'] = 'Có lỗi xảy ra: ' . $e->getMessage();
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function handleHallRequests($controller, $action, $method, &$response, $hallService = null)
{
    try {
        switch ($action) {
            case 'getAll':
                if ($method === 'GET') {
                    $cinemaId = $_GET['cinema_id'] ?? null;
                    if ($cinemaId) {
                        $_GET['cinema_id'] = $cinemaId;
                    }
                    $halls = $controller->getAllHalls();
                    $response['success'] = true;
                    $response['data'] = $halls;
                    $response['message'] = 'Lấy danh sách phòng chiếu thành công';
                } else {
                    throw new Exception('Method không hợp lệ. Sử dụng GET');
                }
                break;

            case 'getById':
                if ($method === 'GET') {
                    $id = $_GET['id'] ?? null;
                    if (!$id) {
                        throw new Exception('Thiếu tham số id');
                    }
                    $hall = $controller->getHallById($id);
                    $response['success'] = true;
                    $response['data'] = $hall;
                    $response['message'] = $hall ? 'Lấy thông tin phòng chiếu thành công' : 'Không tìm thấy phòng chiếu';
                } else {
                    throw new Exception('Method không hợp lệ. Sử dụng GET');
                }
                break;

            case 'getStatuses':
                if ($method === 'GET') {
                    $statuses = $controller->getAllStatuses();
                    $response['success'] = true;
                    $response['data'] = $statuses;
                    $response['message'] = 'Lấy danh sách trạng thái phòng thành công';
                } else {
                    throw new Exception('Method không hợp lệ. Sử dụng GET');
                }
                break;

            case 'getCinemas':
                if ($method === 'GET') {
                    $cinemas = $controller->getAllCinemas();
                    $response['success'] = true;
                    $response['data'] = $cinemas;
                    $response['message'] = 'Lấy danh sách rạp thành công';
                } else {
                    throw new Exception('Method không hợp lệ. Sử dụng GET');
                }
                break;

            case 'create':
                if ($method === 'POST') {
                    $cinemaId = $_POST['cinema_id'] ?? null;
                    $name = $_POST['name'] ?? '';
                    $statusId = $_POST['status_id'] ?? null;
                    $seatCount = $_POST['seat_count'] ?? 0;

                    if (!$cinemaId || !$name || $statusId===null || $statusId==='') {
                        throw new Exception('Vui lòng điền đầy đủ thông tin (cinema_id, name, status_id)');
                    }

                    // Gọi service trực tiếp để tránh redirect
                    if (!$hallService) {
                        throw new Exception('Service không được khởi tạo');
                    }
                    $result = $hallService->createHall($cinemaId, $name, $statusId, $seatCount);

                    if ($result) {
                        $response['success'] = true;
                        $response['message'] = 'Tạo phòng chiếu thành công';
                        $response['data'] = [
                            'hall_id' => is_array($result) ? ($result['hall_id'] ?? null) : null,
                            'cinema_id' => $cinemaId,
                            'name' => $name,
                            'status_id' => $statusId,
                            'seat_count' => is_array($result) ? ($result['seat_count'] ?? (int)$seatCount) : (int)$seatCount
                        ];
                    } else {
                        throw new Exception('Không thể tạo phòng chiếu');
                    }
                } else {
                    throw new Exception('Method không hợp lệ. Sử dụng POST');
                }
                break;

            case 'update':
                if ($method === 'POST') {
                    $id = $_GET['id'] ?? $_POST['id'] ?? null;
                    if (!$id) {
                        throw new Exception('Thiếu tham số id');
                    }

                    $cinemaId = $_POST['cinema_id'] ?? null;
                    $name = $_POST['name'] ?? '';
                    $statusId = $_POST['status_id'] ?? null;

                    if (!$cinemaId || !$name || $statusId===null || $statusId==='') {
                        throw new Exception('Vui lòng điền đầy đủ thông tin (cinema_id, name, status_id)');
                    }

                    // Gọi service trực tiếp để tránh redirect
                    if (!$hallService) {
                        throw new Exception('Service không được khởi tạo');
                    }
                    $result = $hallService->updateHall($id, $cinemaId, $name, $statusId);

                    if ($result) {
                        $response['success'] = true;
                        $response['message'] = 'Cập nhật phòng chiếu thành công';
                        $response['data'] = [
                            'id' => $id,
                            'cinema_id' => $cinemaId,
                            'name' => $name,
                            'status_id' => $statusId
                        ];
                    } else {
                        throw new Exception('Không thể cập nhật phòng chiếu');
                    }
                } else {
                    throw new Exception('Method không hợp lệ. Sử dụng POST');
                }
                break;

            case 'delete':
                if ($method === 'POST' || $method === 'DELETE') {
                    $id = $_GET['id'] ?? $_POST['id'] ?? null;
                    if (!$id) {
                        throw new Exception('Thiếu tham số id');
                    }

                    // Gọi service trực tiếp để tránh redirect
                    if (!$hallService) {
                        throw new Exception('Service không được khởi tạo');
                    }
                    $result = $hallService->deleteHall($id);

                    if ($result) {
                        $response['success'] = true;
                        $response['message'] = 'Xóa phòng chiếu thành công';
                        $response['data'] = ['id' => $id];
                    } else {
                        throw new Exception('Không thể xóa phòng chiếu');
                    }
                } else {
                    throw new Exception('Method không hợp lệ. Sử dụng POST hoặc DELETE');
                }
                break;

            default:
                throw new Exception('Action không hợp lệ. Các action hợp lệ: getAll, getById, getStatuses, getCinemas, create, update, delete');
        }
    } catch (Exception $e) {
        $response['success'] = false;
        $response['error'] = $e->getMessage();
        $response['message'] = 'Có lỗi xảy ra: ' . $e->getMessage();
    }

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function handleSeatTypeRequests($controller, $action, $method, &$response, $seatTypeService = null)
{
    try {
        switch ($action) {
            case 'getAll':
                if ($method === 'GET') {
                    $seatTypes = $controller->getAllSeatTypes();
                    $response['success'] = true;
                    $response['data'] = $seatTypes;
                    $response['message'] = 'Lấy danh sách loại ghế thành công';
                } else {
                    throw new Exception('Method không hợp lệ. Sử dụng GET');
                }
                break;

            case 'getById':
                if ($method === 'GET') {
                    $id = $_GET['id'] ?? null;
                    if (!$id) {
                        throw new Exception('Thiếu tham số id');
                    }
                    $seatType = $controller->getSeatTypeById($id);
                    $response['success'] = true;
                    $response['data'] = $seatType;
                    $response['message'] = $seatType ? 'Lấy thông tin loại ghế thành công' : 'Không tìm thấy loại ghế';
                } else {
                    throw new Exception('Method không hợp lệ. Sử dụng GET');
                }
                break;

            case 'create':
                if ($method === 'POST') {
                    $typeName = $_POST['type_name'] ?? '';
                    $priceSurcharge = $_POST['price_surcharge'] ?? 0;

                    if (empty($typeName)) {
                        throw new Exception('Vui lòng nhập tên loại ghế');
                    }

                    // Tính hệ số giá từ phụ thu (giống như trong controller)
                    $basePrice = 100000; // Giá gốc mặc định
                    $priceMultiplier = ($basePrice + $priceSurcharge) / $basePrice;

                    // Gọi service trực tiếp để tránh redirect
                    if (!$seatTypeService) {
                        throw new Exception('Service không được khởi tạo');
                    }
                    $result = $seatTypeService->createSeatType($typeName, $priceMultiplier);

                    if ($result) {
                        $response['success'] = true;
                        $response['message'] = 'Tạo loại ghế thành công';
                        $response['data'] = [
                            'type_name' => $typeName,
                            'price_surcharge' => $priceSurcharge
                        ];
                    } else {
                        throw new Exception('Không thể tạo loại ghế');
                    }
                } else {
                    throw new Exception('Method không hợp lệ. Sử dụng POST');
                }
                break;

            case 'update':
                if ($method === 'POST') {
                    $id = $_GET['id'] ?? $_POST['id'] ?? null;
                    if (!$id) {
                        throw new Exception('Thiếu tham số id');
                    }

                    $typeName = $_POST['type_name'] ?? '';
                    $priceSurcharge = $_POST['price_surcharge'] ?? 0;

                    if (empty($typeName)) {
                        throw new Exception('Vui lòng nhập tên loại ghế');
                    }

                    // Tính hệ số giá từ phụ thu (giống như trong controller)
                    $basePrice = 100000; // Giá gốc mặc định
                    $priceMultiplier = ($basePrice + $priceSurcharge) / $basePrice;

                    // Gọi service trực tiếp để tránh redirect
                    if (!$seatTypeService) {
                        throw new Exception('Service không được khởi tạo');
                    }
                    $result = $seatTypeService->updateSeatType($id, $typeName, $priceMultiplier);

                    if ($result) {
                        $response['success'] = true;
                        $response['message'] = 'Cập nhật loại ghế thành công';
                        $response['data'] = [
                            'id' => $id,
                            'type_name' => $typeName,
                            'price_surcharge' => $priceSurcharge
                        ];
                    } else {
                        throw new Exception('Không thể cập nhật loại ghế');
                    }
                } else {
                    throw new Exception('Method không hợp lệ. Sử dụng POST');
                }
                break;

            case 'delete':
                if ($method === 'POST' || $method === 'DELETE') {
                    $id = $_GET['id'] ?? $_POST['id'] ?? null;
                    if (!$id) {
                        throw new Exception('Thiếu tham số id');
                    }

                    // Gọi service trực tiếp để tránh redirect
                    if (!$seatTypeService) {
                        throw new Exception('Service không được khởi tạo');
                    }
                    $result = $seatTypeService->deleteSeatType($id);

                    if ($result) {
                        $response['success'] = true;
                        $response['message'] = 'Xóa loại ghế thành công';
                        $response['data'] = ['id' => $id];
                    } else {
                        throw new Exception('Không thể xóa loại ghế');
                    }
                } else {
                    throw new Exception('Method không hợp lệ. Sử dụng POST hoặc DELETE');
                }
                break;

            default:
                throw new Exception('Action không hợp lệ. Các action hợp lệ: getAll, getById, create, update, delete');
        }
    } catch (Exception $e) {
        $response['success'] = false;
        $response['error'] = $e->getMessage();
        $response['message'] = 'Có lỗi xảy ra: ' . $e->getMessage();
    }

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function handleCinemaRequests($controller, $action, $method, &$response, $cinemaService = null)
{
    try {
        switch ($action) {
            case 'getAll':
                if ($method === 'GET') {
                    $cinemas = $controller->getAllCinemas();
                    $response['success'] = true;
                    $response['data'] = $cinemas;
                    $response['message'] = 'Lấy danh sách rạp chiếu thành công';
                } else {
                    throw new Exception('Method không hợp lệ. Sử dụng GET');
                }
                break;

            case 'getById':
                if ($method === 'GET') {
                    $id = $_GET['id'] ?? null;
                    if (!$id) {
                        throw new Exception('Thiếu tham số id');
                    }
                    $cinema = $controller->getCinemaById($id);
                    $response['success'] = true;
                    $response['data'] = $cinema;
                    $response['message'] = $cinema ? 'Lấy thông tin rạp chiếu thành công' : 'Không tìm thấy rạp chiếu';
                } else {
                    throw new Exception('Method không hợp lệ. Sử dụng GET');
                }
                break;

            case 'getLocations':
                if ($method === 'GET') {
                    $locations = $controller->getAllLocations();
                    $response['success'] = true;
                    $response['data'] = $locations;
                    $response['message'] = 'Lấy danh sách địa điểm thành công';
                } else {
                    throw new Exception('Method không hợp lệ. Sử dụng GET');
                }
                break;

            case 'getStatuses':
                if ($method === 'GET') {
                    $statuses = $controller->getAllCinemaStatuses();
                    $response['success'] = true;
                    $response['data'] = $statuses;
                    $response['message'] = 'Lấy danh sách trạng thái rạp thành công';
                } else {
                    throw new Exception('Method không hợp lệ. Sử dụng GET');
                }
                break;

            case 'create':
                if ($method === 'POST') {
                    $name = $_POST['name'] ?? '';
                    $address = $_POST['address'] ?? '';
                    $locationId = $_POST['location_id'] ?? null;
                    $statusId = $_POST['status_id'] ?? null;

                    if (!$name || !$address || !$locationId || $statusId===null || $statusId==='') {
                        throw new Exception('Vui lòng điền đầy đủ thông tin (name, address, location_id, status_id)');
                    }

                    // Gọi service trực tiếp để tránh redirect
                    if (!$cinemaService) {
                        throw new Exception('Service không được khởi tạo');
                    }
                    $result = $cinemaService->createCinema($name, $address, $locationId, $statusId);

                    if ($result) {
                        $response['success'] = true;
                        $response['message'] = 'Tạo rạp chiếu thành công';
                        $response['data'] = [
                            'name' => $name,
                            'address' => $address,
                            'location_id' => $locationId,
                            'status_id' => $statusId,
                            'id' => $result
                        ];
                    } else {
                        throw new Exception('Không thể tạo rạp chiếu');
                    }
                } else {
                    throw new Exception('Method không hợp lệ. Sử dụng POST');
                }
                break;

            case 'update':
                if ($method === 'POST') {
                    $id = $_GET['id'] ?? $_POST['id'] ?? null;
                    if (!$id) {
                        throw new Exception('Thiếu tham số id');
                    }

                    $name = $_POST['name'] ?? '';
                    $address = $_POST['address'] ?? '';
                    $locationId = $_POST['location_id'] ?? null;
                    $statusId = $_POST['status_id'] ?? null;

                    if (!$name || !$address || !$locationId || $statusId===null || $statusId==='') {
                        throw new Exception('Vui lòng điền đầy đủ thông tin (name, address, location_id, status_id)');
                    }

                    // Gọi service trực tiếp để tránh redirect
                    if (!$cinemaService) {
                        throw new Exception('Service không được khởi tạo');
                    }
                    $result = $cinemaService->updateCinema($id, $name, $address, $locationId, $statusId);

                    if ($result) {
                        $response['success'] = true;
                        $response['message'] = 'Cập nhật rạp chiếu thành công';
                        $response['data'] = [
                            'id' => $id,
                            'name' => $name,
                            'address' => $address,
                            'location_id' => $locationId,
                            'status_id' => $statusId
                        ];
                    } else {
                        throw new Exception('Không thể cập nhật rạp chiếu');
                    }
                } else {
                    throw new Exception('Method không hợp lệ. Sử dụng POST');
                }
                break;

            case 'delete':
                if ($method === 'POST' || $method === 'DELETE') {
                    $id = $_GET['id'] ?? $_POST['id'] ?? null;
                    if (!$id) {
                        throw new Exception('Thiếu tham số id');
                    }

                    // Gọi service trực tiếp để tránh redirect
                    if (!$cinemaService) {
                        throw new Exception('Service không được khởi tạo');
                    }
                    $result = $cinemaService->deleteCinema($id);

                    if ($result) {
                        $response['success'] = true;
                        $response['message'] = 'Xóa rạp chiếu thành công';
                        $response['data'] = ['id' => $id];
                    } else {
                        throw new Exception('Không thể xóa rạp chiếu');
                    }
                } else {
                    throw new Exception('Method không hợp lệ. Sử dụng POST hoặc DELETE');
                }
                break;

            default:
                throw new Exception('Action không hợp lệ. Các action hợp lệ: getAll, getById, getLocations, getStatuses, create, update, delete');
        }
    } catch (Exception $e) {
        $response['success'] = false;
        $response['error'] = $e->getMessage();
        $response['message'] = 'Có lỗi xảy ra: ' . $e->getMessage();
    }

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function handleSeatRequests($controller, $action, $method, &$response, $seatService = null)
{
    try {
        switch ($action) {
            case 'getByHall':
                if ($method === 'GET') {
                    $hallId = $_GET['hall_id'] ?? null;
                    if (!$hallId) {
                        throw new Exception('Thiếu tham số hall_id');
                    }
                    $seats = $controller->getSeatsByHall($hallId);
                    $response['success'] = true;
                    $response['data'] = $seats;
                    $response['message'] = 'Lấy danh sách ghế thành công';
                } else {
                    throw new Exception('Method không hợp lệ. Sử dụng GET');
                }
                break;

            case 'getById':
                if ($method === 'GET') {
                    $id = $_GET['id'] ?? null;
                    if (!$id) {
                        throw new Exception('Thiếu tham số id');
                    }
                    $seat = $controller->getSeatById($id);
                    $response['success'] = true;
                    $response['data'] = $seat;
                    $response['message'] = $seat ? 'Lấy thông tin ghế thành công' : 'Không tìm thấy ghế';
                } else {
                    throw new Exception('Method không hợp lệ. Sử dụng GET');
                }
                break;

            case 'create':
                if ($method === 'POST') {
                    $hallId = $_POST['hall_id'] ?? null;
                    $seatTypeId = $_POST['seat_type_id'] ?? null;
                    $rowName = $_POST['row_name'] ?? '';
                    $seatNumber = $_POST['seat_number'] ?? null;

                    if (!$hallId || !$seatTypeId || !$rowName || !$seatNumber) {
                        throw new Exception('Vui lòng điền đầy đủ thông tin (hall_id, seat_type_id, row_name, seat_number)');
                    }

                    if (!$seatService) {
                        throw new Exception('Service không được khởi tạo');
                    }
                    $result = $seatService->createSeat($hallId, $seatTypeId, $rowName, $seatNumber);

                    if ($result) {
                        $response['success'] = true;
                        $response['message'] = 'Tạo ghế thành công';
                        $response['data'] = [
                            'id' => $result,
                            'hall_id' => $hallId,
                            'seat_type_id' => $seatTypeId,
                            'row_name' => $rowName,
                            'seat_number' => $seatNumber
                        ];
                    } else {
                        throw new Exception('Không thể tạo ghế');
                    }
                } else {
                    throw new Exception('Method không hợp lệ. Sử dụng POST');
                }
                break;

            case 'update':
                if ($method === 'POST') {
                    $id = $_GET['id'] ?? $_POST['id'] ?? null;
                    if (!$id) {
                        throw new Exception('Thiếu tham số id');
                    }

                    $seatTypeId = $_POST['seat_type_id'] ?? null;
                    if (!$seatTypeId) {
                        throw new Exception('Vui lòng chọn loại ghế');
                    }

                    if (!$seatService) {
                        throw new Exception('Service không được khởi tạo');
                    }
                    $result = $seatService->updateSeat($id, $seatTypeId);

                    if ($result) {
                        $response['success'] = true;
                        $response['message'] = 'Cập nhật ghế thành công';
                        $response['data'] = [
                            'id' => $id,
                            'seat_type_id' => $seatTypeId
                        ];
                    } else {
                        throw new Exception('Không thể cập nhật ghế');
                    }
                } else {
                    throw new Exception('Method không hợp lệ. Sử dụng POST');
                }
                break;

            case 'delete':
                if ($method === 'POST' || $method === 'DELETE') {
                    $id = $_GET['id'] ?? $_POST['id'] ?? null;
                    if (!$id) {
                        throw new Exception('Thiếu tham số id');
                    }

                    if (!$seatService) {
                        throw new Exception('Service không được khởi tạo');
                    }
                    $result = $seatService->deleteSeat($id);

                    if ($result) {
                        $response['success'] = true;
                        $response['message'] = 'Xóa ghế thành công';
                        $response['data'] = ['id' => $id];
                    } else {
                        throw new Exception('Không thể xóa ghế');
                    }
                } else {
                    throw new Exception('Method không hợp lệ. Sử dụng POST hoặc DELETE');
                }
                break;

            case 'deleteAll':
                if ($method === 'POST') {
                    $hallId = $_GET['hall_id'] ?? $_POST['hall_id'] ?? null;
                    if (!$hallId) {
                        throw new Exception('Thiếu tham số hall_id');
                    }

                    if (!$seatService) {
                        throw new Exception('Service không được khởi tạo');
                    }
                    $result = $seatService->deleteAllSeatsByHall($hallId);

                    if ($result !== false) {
                        $response['success'] = true;
                        $response['message'] = 'Xóa sơ đồ ghế thành công';
                        $response['data'] = ['hall_id' => $hallId, 'deleted_count' => $result];
                    } else {
                        throw new Exception('Không thể xóa sơ đồ ghế');
                    }
                } else {
                    throw new Exception('Method không hợp lệ. Sử dụng POST');
                }
                break;

            case 'createBulk':
                if ($method === 'POST') {
                    $hallId = $_POST['hall_id'] ?? null;
                    $seats = json_decode($_POST['seats'] ?? '[]', true);

                    if (!$hallId || empty($seats)) {
                        throw new Exception('Vui lòng điền đầy đủ thông tin (hall_id, seats)');
                    }

                    if (!$seatService) {
                        throw new Exception('Service không được khởi tạo');
                    }
                    $result = $seatService->createBulkSeats($hallId, $seats);

                    $response['success'] = true;
                    $response['message'] = "Tạo sơ đồ ghế thành công ({$result['success_count']} ghế)";
                    $response['data'] = $result;
                    if (!empty($result['errors'])) {
                        $response['warnings'] = $result['errors'];
                    }
                } else {
                    throw new Exception('Method không hợp lệ. Sử dụng POST');
                }
                break;

            default:
                throw new Exception('Action không hợp lệ. Các action hợp lệ: getByHall, getById, create, update, delete, deleteAll, createBulk');
        }
    } catch (Exception $e) {
        $response['success'] = false;
        $response['error'] = $e->getMessage();
        $response['message'] = 'Có lỗi xảy ra: ' . $e->getMessage();
    }

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function handleShowRequests($showModel, $action, $method, &$response)
{
    try {
        switch ($action) {
            case 'getAllForUser':
            case 'getShowtimes':
                if ($method !== 'GET') {
                    throw new Exception('Method không hợp lệ. Sử dụng GET');
                }
                $cinemaId = $_GET['cinema_id'] ?? null;
                $movieId = $_GET['movie_id'] ?? null;
                $showDate = $_GET['show_date'] ?? null;

                $conn = $showModel->getConnection();
                $sql = "SELECT s.show_id, s.movie_id, s.hall_id, s.show_date, s.start_time, s.end_time, s.base_price, s.status,
                               m.title AS movie_title, m.poster_url AS movie_poster_url, m.duration_min AS movie_duration_min, m.director AS movie_director,
                               h.name AS hall_name, h.status AS hall_status,
                               c.name AS cinema_name, c.cinema_id, c.status AS cinema_status
                        FROM shows s
                        INNER JOIN movies m ON s.movie_id = m.movie_id
                        INNER JOIN halls h ON s.hall_id = h.hall_id
                        INNER JOIN cinemas c ON h.cinema_id = c.cinema_id
                        WHERE s.status IN (0, 1)
                          AND h.status = 1
                          AND c.status = 1
                          AND (
                              s.show_date > CURDATE()
                              OR (s.show_date = CURDATE() AND s.end_time >= CURTIME())
                          )";
                $types = '';
                $params = [];
                if ($cinemaId !== null && $cinemaId !== '') {
                    $sql .= " AND c.cinema_id = ?";
                    $types .= 'i';
                    $params[] = (int)$cinemaId;
                }
                if ($movieId !== null && $movieId !== '') {
                    $sql .= " AND s.movie_id = ?";
                    $types .= 'i';
                    $params[] = (int)$movieId;
                }
                if ($showDate !== null && $showDate !== '') {
                    $sql .= " AND s.show_date = ?";
                    $types .= 's';
                    $params[] = $showDate;
                }
                $sql .= " ORDER BY s.show_date ASC, c.name ASC, h.name ASC, s.start_time ASC";

                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception('SQL Error: ' . $conn->error);
                }
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                if (!$stmt->execute()) {
                    throw new Exception('SQL Execute Error: ' . $stmt->error);
                }
                $result = $stmt->get_result();
                $shows = [];
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $shows[] = $row;
                    }
                }
                $response['success'] = true;
                $response['data'] = $shows;
                $response['message'] = 'Lấy danh sách suất chiếu user thành công';
                break;

            case 'getById':
                if ($method !== 'GET') {
                    throw new Exception('Method không hợp lệ. Sử dụng GET');
                }
                $id = $_GET['id'] ?? null;
                if (!$id) {
                    throw new Exception('Thiếu tham số id');
                }

                $conn = $showModel->getConnection();
                $sql = "SELECT s.show_id, s.movie_id, s.hall_id, s.show_date, s.start_time, s.end_time, s.base_price, s.status,
                               m.title AS movie_title, h.name AS hall_name, c.name AS cinema_name
                        FROM shows s
                        INNER JOIN movies m ON s.movie_id = m.movie_id
                        INNER JOIN halls h ON s.hall_id = h.hall_id
                        INNER JOIN cinemas c ON h.cinema_id = c.cinema_id
                        WHERE s.show_id = ?
                        LIMIT 1";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception('SQL Error: ' . $conn->error);
                }
                $idInt = (int)$id;
                $stmt->bind_param("i", $idInt);
                if (!$stmt->execute()) {
                    throw new Exception('SQL Execute Error: ' . $stmt->error);
                }
                $result = $stmt->get_result();
                $show = $result ? $result->fetch_assoc() : null;

                $response['success'] = true;
                $response['data'] = $show;
                $response['message'] = $show ? 'Lấy thông tin suất chiếu thành công' : 'Không tìm thấy suất chiếu';
                break;

            default:
                throw new Exception('Action không hợp lệ. Các action hợp lệ: getAllForUser, getById');
        }
    } catch (Exception $e) {
        $response['success'] = false;
        $response['error'] = $e->getMessage();
        $response['message'] = 'Có lỗi xảy ra: ' . $e->getMessage();
    }

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function showApiList()
{
    $apiList = [
        'title' => 'Danh sách API Test',
        'description' => 'Sử dụng: test_api.php?controller={controller}&action={action}&id={id}',
        'controllers' => [
            'halls' => [
                'description' => 'Quản lý phòng chiếu',
                'actions' => [
                    'getAll' => 'GET - Lấy danh sách phòng chiếu (có thể thêm ?cinema_id=1)',
                    'getById' => 'GET - Lấy phòng chiếu theo ID (?id=1)',
                    'getStatuses' => 'GET - Lấy danh sách trạng thái phòng',
                    'getCinemas' => 'GET - Lấy danh sách rạp',
                    'create' => 'POST - Tạo phòng chiếu mới (POST: cinema_id, name, status_id)',
                    'update' => 'POST - Cập nhật phòng chiếu (?id=1, POST: cinema_id, name, status_id)',
                    'delete' => 'POST/DELETE - Xóa phòng chiếu (?id=1)'
                ]
            ],
            'seat_types' => [
                'description' => 'Quản lý loại ghế',
                'actions' => [
                    'getAll' => 'GET - Lấy danh sách loại ghế',
                    'getById' => 'GET - Lấy loại ghế theo ID (?id=1)',
                    'create' => 'POST - Tạo loại ghế mới (POST: type_name, price_surcharge)',
                    'update' => 'POST - Cập nhật loại ghế (?id=1, POST: type_name, price_surcharge)',
                    'delete' => 'POST/DELETE - Xóa loại ghế (?id=1)'
                ]
            ]
        ],
        'examples' => [
            'GET - Lấy tất cả phòng chiếu' => 'test_api.php?controller=halls&action=getAll',
            'GET - Lấy phòng chiếu theo ID' => 'test_api.php?controller=halls&action=getById&id=1',
            'GET - Lấy phòng chiếu theo rạp' => 'test_api.php?controller=halls&action=getAll&cinema_id=1',
            'GET - Lấy tất cả loại ghế' => 'test_api.php?controller=seat_types&action=getAll'
        ]
    ];

    echo json_encode($apiList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
