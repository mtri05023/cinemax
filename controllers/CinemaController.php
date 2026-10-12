<?php

require_once __DIR__ . '/../services/CinemaService.php';
require_once __DIR__ . '/../config/dbConfig.php';

class CinemaController
{
    private CinemaService $cinemaService;

    public function __construct(CinemaService $cinemaService)
    {
        $this->cinemaService = $cinemaService;
    }

    public function getAllCinemas()
    {
        try {
            // Trang admin cần thấy cả rạp đã đóng để hiển thị mờ và cho phép mở lại
            return $this->cinemaService->getAllCinemas(true);
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            return [];
        }
    }

    public function getCinemaById($cinemaId)
    {
        try {
            return $this->cinemaService->getCinemaById($cinemaId);
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            return null;
        }
    }

    public function createCinema()
    {
        try {
            $name = $_POST['name'] ?? '';
            $address = $_POST['address'] ?? '';
            $locationId = $_POST['location_id'] ?? null;
            $statusId = $_POST['status_id'] ?? null;

            if (!$name || !$address || $locationId === null || $locationId === '' || $statusId === null || $statusId === '') {
                throw new InvalidArgumentException("Vui lòng điền đầy đủ thông tin.");
            }

            $result = $this->cinemaService->createCinema($name, $address, $locationId, $statusId);

            if ($result) {
                $_SESSION['success'] = "Thêm rạp chiếu thành công!";
                header('Location: ../../views/admin/index.php?page=cinemas&add=1');
            } else {
                throw new Exception("Không thể thêm rạp chiếu.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ../../views/admin/index.php?page=cinemas&error=1');
        }
    }

    public function updateCinema($id)
    {
        try {
            $name = $_POST['name'] ?? '';
            $address = $_POST['address'] ?? '';
            $locationId = $_POST['location_id'] ?? null;
            $statusId = $_POST['status_id'] ?? null;

            if (!$name || !$address || $locationId === null || $locationId === '' || $statusId === null || $statusId === '') {
                throw new InvalidArgumentException("Vui lòng điền đầy đủ thông tin.");
            }

            $result = $this->cinemaService->updateCinema($id, $name, $address, $locationId, $statusId);

            if ($result) {
                $_SESSION['success'] = "Cập nhật rạp chiếu thành công!";
                header('Location: ../../views/admin/index.php?page=cinemas&update=1');
            } else {
                throw new Exception("Không thể cập nhật rạp chiếu.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ../../views/admin/index.php?page=cinemas&error=1');
        }
    }

    public function deleteCinema($id)
    {
        try {
            $result = $this->cinemaService->deleteCinema($id);

            if ($result) {
                $_SESSION['success'] = "Đóng rạp thành công (ngừng hoạt động)!";
                header('Location: ../../views/admin/index.php?page=cinemas&delete=1');
            } else {
                throw new Exception("Không thể đóng rạp.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: ../../views/admin/index.php?page=cinemas&error=1');
        }
    }

    public function getAllLocations()
    {
        try {
            return $this->cinemaService->getAllLocations();
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            return [];
        }
    }

    public function getAllCinemaStatuses()
    {
        try {
            return $this->cinemaService->getAllCinemaStatuses();
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            return [];
        }
    }
}
