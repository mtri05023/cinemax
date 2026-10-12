<?php

require_once __DIR__ . '/../services/SeatService.php';
require_once __DIR__ . '/../config/dbConfig.php';

class SeatController
{
    private SeatService $seatService;

    public function __construct(SeatService $seatService)
    {
        $this->seatService = $seatService;
    }

    public function getSeatsByHall($hallId)
    {
        try {
            return $this->seatService->getSeatsByHall($hallId);
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            return [];
        }
    }

    public function getSeatById($seatId)
    {
        try {
            return $this->seatService->getSeatById($seatId);
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            return null;
        }
    }

    public function createSeat()
    {
        try {
            $hallId = $_POST['hall_id'] ?? null;
            $seatTypeId = $_POST['seat_type_id'] ?? null;
            $rowName = $_POST['row_name'] ?? '';
            $seatNumber = $_POST['seat_number'] ?? null;

            if (!$hallId || !$seatTypeId || !$rowName || !$seatNumber) {
                throw new InvalidArgumentException("Vui lòng điền đầy đủ thông tin.");
            }

            $result = $this->seatService->createSeat($hallId, $seatTypeId, $rowName, $seatNumber);

            if ($result) {
                $_SESSION['success'] = "Thêm ghế thành công!";
                header('Location: ../../views/admin/index.php?page=seats&hall_id=' . $hallId . '&add=1');
            } else {
                throw new Exception("Không thể thêm ghế.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ../../views/admin/index.php?page=seats&hall_id=' . ($_POST['hall_id'] ?? '') . '&error=1');
        }
    }

    public function updateSeat($seatId)
    {
        try {
            $seatTypeId = $_POST['seat_type_id'] ?? null;

            if (!$seatTypeId) {
                throw new InvalidArgumentException("Vui lòng chọn loại ghế.");
            }

            $result = $this->seatService->updateSeat($seatId, $seatTypeId);

            if ($result) {
                $_SESSION['success'] = "Cập nhật ghế thành công!";
                header('Location: ../../views/admin/index.php?page=seats&hall_id=' . ($_GET['hall_id'] ?? $_POST['hall_id'] ?? '') . '&update=1');
            } else {
                throw new Exception("Không thể cập nhật ghế.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ../../views/admin/index.php?page=seats&hall_id=' . ($_GET['hall_id'] ?? $_POST['hall_id'] ?? '') . '&error=1');
        }
    }

    public function deleteSeat($seatId)
    {
        try {
            $result = $this->seatService->deleteSeat($seatId);

            if ($result) {
                $_SESSION['success'] = "Xóa ghế thành công!";
                header('Location: ../../views/admin/index.php?page=seats&hall_id=' . ($_GET['hall_id'] ?? '') . '&delete=1');
            } else {
                throw new Exception("Không thể xóa ghế.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ../../views/admin/index.php?page=seats&hall_id=' . ($_GET['hall_id'] ?? '') . '&error=1');
        }
    }

    public function deleteAllSeatsByHall($hallId)
    {
        try {
            $result = $this->seatService->deleteAllSeatsByHall($hallId);

            if ($result !== false) {
                $_SESSION['success'] = "Xóa sơ đồ ghế thành công!";
                header('Location: ../../views/admin/index.php?page=seats&hall_id=' . $hallId . '&reset=1');
            } else {
                throw new Exception("Không thể xóa sơ đồ ghế.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ../../views/admin/index.php?page=seats&hall_id=' . $hallId . '&error=1');
        }
    }

    public function createBulkSeats()
    {
        try {
            $hallId = $_POST['hall_id'] ?? null;
            $seats = json_decode($_POST['seats'] ?? '[]', true);

            if (!$hallId || empty($seats)) {
                throw new InvalidArgumentException("Vui lòng điền đầy đủ thông tin.");
            }

            $result = $this->seatService->createBulkSeats($hallId, $seats);

            $_SESSION['success'] = "Tạo sơ đồ ghế thành công! ({$result['success_count']} ghế)";
            if (!empty($result['errors'])) {
                $_SESSION['warning'] = "Một số ghế không thể tạo: " . implode(', ', $result['errors']);
            }
            header('Location: ../../views/admin/index.php?page=seats&hall_id=' . $hallId . '&bulk=1');
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ../../views/admin/index.php?page=seats&hall_id=' . ($_POST['hall_id'] ?? '') . '&error=1');
        }
    }
}
