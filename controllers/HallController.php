<?php

require_once __DIR__ . '/../services/HallService.php';
require_once __DIR__ . '/../config/dbConfig.php';

class HallController
{
    private HallService $hallService;

    public function __construct(HallService $hallService)
    {
        $this->hallService = $hallService;
    }

    public function getAllHalls()
    {
        try {
            $cinemaId = $_GET['cinema_id'] ?? null;
            $halls = $this->hallService->getAllHalls($cinemaId);
            
            // Lấy số ghế cho mỗi phòng
            foreach ($halls as &$hall) {
                $hallId = $hall['hall_id'] ?? $hall['HallID'] ?? 0;
                $hall['SeatCount'] = $this->hallService->getSeatCount($hallId);
            }
            
            return $halls;
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            return [];
        }
    }

    public function getHallById($hallId)
    {
        try {
            return $this->hallService->getHallById($hallId);
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            return null;
        }
    }

    public function createHall()
    {
        try {
            $cinemaId = $_POST['cinema_id'] ?? null;
            $name = $_POST['name'] ?? '';
            $statusId = $_POST['status_id'] ?? null;
            $seatCount = $_POST['seat_count'] ?? 0;

            if (!$cinemaId || !$name || !$statusId) {
                throw new InvalidArgumentException("Vui lòng điền đầy đủ thông tin.");
            }

            $result = $this->hallService->createHall($cinemaId, $name, $statusId, $seatCount);

            if ($result) {
                $_SESSION['success'] = "Thêm phòng chiếu thành công!";
                header('Location: ../../views/admin/index.php?page=halls&add=1');
            } else {
                throw new Exception("Không thể thêm phòng chiếu.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ../../views/admin/index.php?page=halls&error=1');
        }
    }

    public function updateHall($id)
    {
        try {
            $cinemaId = $_POST['cinema_id'] ?? null;
            $name = $_POST['name'] ?? '';
            $statusId = $_POST['status_id'] ?? null;

            if (!$cinemaId || !$name || !$statusId) {
                throw new InvalidArgumentException("Vui lòng điền đầy đủ thông tin.");
            }

            $result = $this->hallService->updateHall($id, $cinemaId, $name, $statusId);

            if ($result) {
                $_SESSION['success'] = "Cập nhật phòng chiếu thành công!";
                header('Location: ../../views/admin/index.php?page=halls&update=1');
            } else {
                throw new Exception("Không thể cập nhật phòng chiếu.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ../../views/admin/index.php?page=halls&error=1');
        }
    }

    public function deleteHall($id)
    {
        try {
            $result = $this->hallService->deleteHall($id);

            if ($result) {
                $_SESSION['success'] = "Xóa phòng chiếu thành công!";
                header('Location: ../../views/admin/index.php?page=halls&delete=1');
            } else {
                throw new Exception("Không thể xóa phòng chiếu.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ../../views/admin/index.php?page=halls&error=1');
        }
    }

    public function getAllStatuses()
    {
        return [
            ['StatusID' => 1, 'StatusName' => 'Đang hoạt động'],
            ['StatusID' => 0, 'StatusName' => 'Tạm dừng'],
            ['StatusID' => 2, 'StatusName' => 'Bảo trì'],
        ];
    }

    // Helper function để lấy danh sách cinemas
    public function getAllCinemas()
    {
        $conn = getDBConnection();
        $sql = "SELECT cinema_id as CinemaID, name as Name FROM cinemas WHERE status = 1 ORDER BY name";
        $result = $conn->query($sql);
        
        $cinemas = [];
        while ($row = $result->fetch_assoc()) {
            $cinemas[] = $row;
        }
        
        return $cinemas;
    }
}

