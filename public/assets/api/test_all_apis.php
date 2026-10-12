<?php
/**
 * Script test tự động tất cả các API
 * Chạy file này để kiểm tra xem các API có hoạt động đúng không
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <title>Test Tất Cả API</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; }
        .test-section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 5px; }
        .test-item { margin: 10px 0; padding: 10px; background: white; border-left: 4px solid #ddd; }
        .test-item.success { border-left-color: #4CAF50; }
        .test-item.error { border-left-color: #f44336; }
        .test-item.warning { border-left-color: #ff9800; }
        .test-name { font-weight: bold; color: #333; }
        .test-result { margin-top: 5px; padding: 8px; background: #f5f5f5; border-radius: 3px; font-family: monospace; font-size: 12px; }
        .summary { margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 5px; }
        .summary-item { margin: 5px 0; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🧪 Test Tất Cả API - Cinemax</h1>";

require_once __DIR__ . '/config/dbConfig.php';
require_once __DIR__ . '/models/Hall.php';
require_once __DIR__ . '/models/SeatType.php';
require_once __DIR__ . '/services/HallService.php';
require_once __DIR__ . '/services/SeatTypeService.php';
require_once __DIR__ . '/controllers/HallController.php';
require_once __DIR__ . '/controllers/SeatTypeController.php';

$results = [
    'total' => 0,
    'success' => 0,
    'error' => 0,
    'warning' => 0
];

function testAPI($name, $callback, &$results) {
    $results['total']++;
    echo "<div class='test-item'>";
    echo "<div class='test-name'>🔍 Test: $name</div>";
    
    try {
        $result = $callback();
        
        if ($result === false || $result === null) {
            $results['warning']++;
            echo "<div class='test-result warning'>⚠️ Warning: Trả về null hoặc false (có thể không có dữ liệu)</div>";
            echo "</div>";
            return;
        }
        
        if (is_array($result) && count($result) === 0) {
            $results['warning']++;
            echo "<div class='test-result warning'>⚠️ Warning: Trả về mảng rỗng (có thể chưa có dữ liệu trong database)</div>";
            echo "</div>";
            return;
        }
        
        $results['success']++;
        $resultJson = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $resultPreview = strlen($resultJson) > 500 ? substr($resultJson, 0, 500) . '...' : $resultJson;
        echo "<div class='test-result success'>✅ Success: " . htmlspecialchars($resultPreview) . "</div>";
        echo "</div>";
    } catch (Exception $e) {
        $results['error']++;
        echo "<div class='test-result error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        echo "</div>";
    }
}

// Khởi tạo
try {
    $conn = getDBConnection();
    
    $hallModel = new Hall($conn);
    $seatTypeModel = new SeatType($conn);
    
    $hallService = new HallService($hallModel);
    $seatTypeService = new SeatTypeService($seatTypeModel);
    
    $hallController = new HallController($hallService);
    $seatTypeController = new SeatTypeController($seatTypeService);

    echo "<div class='test-section'><h2>1. Seat Types API (Loại ghế)</h2>";
    
    // Test Seat Types
    testAPI("SeatType - getAllSeatTypes()", function() use ($seatTypeController) {
        return $seatTypeController->getAllSeatTypes();
    }, $results);
    
    testAPI("SeatType - getSeatTypeById(1)", function() use ($seatTypeController) {
        return $seatTypeController->getSeatTypeById(1);
    }, $results);
    
    echo "</div>";
    
    echo "<div class='test-section'><h2>2. Halls API (Phòng chiếu)</h2>";
    
    // Test Halls
    testAPI("Hall - getAllHalls()", function() use ($hallController) {
        return $hallController->getAllHalls();
    }, $results);
    
    testAPI("Hall - getHallById(1)", function() use ($hallController) {
        return $hallController->getHallById(1);
    }, $results);
    
    testAPI("Hall - getAllStatuses()", function() use ($hallController) {
        return $hallController->getAllStatuses();
    }, $results);
    
    testAPI("Hall - getAllCinemas()", function() use ($hallController) {
        return $hallController->getAllCinemas();
    }, $results);
    
    testAPI("Hall - getAllHalls(cinema_id=1)", function() use ($hallController) {
        $_GET['cinema_id'] = 1;
        return $hallController->getAllHalls();
    }, $results);
    
    echo "</div>";
    
    // Tóm tắt
    echo "<div class='summary'>";
    echo "<h2>📊 Tóm tắt kết quả</h2>";
    echo "<div class='summary-item'><strong>Tổng số test:</strong> {$results['total']}</div>";
    echo "<div class='summary-item' style='color: #4CAF50;'><strong>✅ Thành công:</strong> {$results['success']}</div>";
    echo "<div class='summary-item' style='color: #ff9800;'><strong>⚠️ Cảnh báo:</strong> {$results['warning']}</div>";
    echo "<div class='summary-item' style='color: #f44336;'><strong>❌ Lỗi:</strong> {$results['error']}</div>";
    
    $successRate = $results['total'] > 0 ? round(($results['success'] / $results['total']) * 100, 2) : 0;
    echo "<div class='summary-item'><strong>Tỷ lệ thành công:</strong> {$successRate}%</div>";
    
    if ($results['error'] == 0 && $results['warning'] == 0) {
        echo "<div style='margin-top: 15px; padding: 10px; background: #c8e6c9; border-radius: 5px; color: #2e7d32;'><strong>🎉 Tất cả API đều hoạt động tốt!</strong></div>";
    } elseif ($results['error'] == 0) {
        echo "<div style='margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 5px; color: #856404;'><strong>⚠️ API hoạt động nhưng có một số cảnh báo (có thể do chưa có dữ liệu)</strong></div>";
    } else {
        echo "<div style='margin-top: 15px; padding: 10px; background: #ffcdd2; border-radius: 5px; color: #c62828;'><strong>❌ Có lỗi xảy ra. Vui lòng kiểm tra lại database và code.</strong></div>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test-item error'>";
    echo "<div class='test-name'>❌ Lỗi khởi tạo</div>";
    echo "<div class='test-result error'>" . htmlspecialchars($e->getMessage()) . "</div>";
    echo "</div>";
}

echo "</div></body></html>";
?>