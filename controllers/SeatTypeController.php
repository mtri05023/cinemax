<?php

require_once __DIR__ . '/../services/SeatTypeService.php';
require_once __DIR__ . '/../config/dbConfig.php';

class SeatTypeController
{
    private SeatTypeService $seatTypeService;

    public function __construct(SeatTypeService $seatTypeService)
    {
        $this->seatTypeService = $seatTypeService;
    }

    public function getAllSeatTypes()
    {
        try {
            return $this->seatTypeService->getAllSeatTypes();
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            return [];
        }
    }

    public function getSeatTypeById($seatTypeId)
    {
        try {
            return $this->seatTypeService->getSeatTypeById($seatTypeId);
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            return null;
        }
    }

    public function createSeatType()
    {
        try {
            $typeName = $_POST['type_name'] ?? '';
            $priceSurcharge = $_POST['price_surcharge'] ?? 0;

            if (empty($typeName)) {
                throw new InvalidArgumentException("Vui lòng nhập tên loại ghế.");
            }

            // Chuyển đổi phụ thu (VNĐ) sang hệ số giá
            // Nếu phụ thu là 50,000 VNĐ và giá gốc là 100,000 VNĐ, thì hệ số = 1.5
            // Ở đây ta sẽ lưu phụ thu trực tiếp, hoặc có thể tính hệ số dựa trên giá gốc
            // Tạm thời lưu phụ thu dưới dạng hệ số (phụ thu / 100000 + 1)
            // Hoặc đơn giản hơn: lưu phụ thu trực tiếp vào PriceMultiplier (nếu giá gốc = 0 thì dùng phụ thu)
            // Giả sử giá gốc là 100,000 VNĐ, thì hệ số = (100000 + phụ thu) / 100000
            $basePrice = 100000; // Giá gốc mặc định
            $priceMultiplier = ($basePrice + $priceSurcharge) / $basePrice;

            $result = $this->seatTypeService->createSeatType($typeName, $priceMultiplier);

            if ($result) {
                $_SESSION['success'] = "Thêm loại ghế thành công!";
                header('Location: ../../views/admin/index.php?page=seat_types&add=1');
            } else {
                throw new Exception("Không thể thêm loại ghế.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ../../views/admin/index.php?page=seat_types&error=1');
        }
    }

    public function updateSeatType($id)
    {
        try {
            $typeName = $_POST['type_name'] ?? '';
            $priceSurcharge = $_POST['price_surcharge'] ?? 0;

            if (empty($typeName)) {
                throw new InvalidArgumentException("Vui lòng nhập tên loại ghế.");
            }

            // Tính hệ số giá tương tự như create
            $basePrice = 100000; // Giá gốc mặc định
            $priceMultiplier = ($basePrice + $priceSurcharge) / $basePrice;

            $result = $this->seatTypeService->updateSeatType($id, $typeName, $priceMultiplier);

            if ($result) {
                $_SESSION['success'] = "Cập nhật loại ghế thành công!";
                header('Location: ../../views/admin/index.php?page=seat_types&update=1');
            } else {
                throw new Exception("Không thể cập nhật loại ghế.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ../../views/admin/index.php?page=seat_types&error=1');
        }
    }

    public function deleteSeatType($id)
    {
        try {
            $result = $this->seatTypeService->deleteSeatType($id);

            if ($result) {
                $_SESSION['success'] = "Xóa loại ghế thành công!";
                header('Location: ../../views/admin/index.php?page=seat_types&delete=1');
            } else {
                throw new Exception("Không thể xóa loại ghế.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ../../views/admin/index.php?page=seat_types&error=1');
        }
    }
}

