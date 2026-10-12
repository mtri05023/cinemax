<?php
require_once __DIR__ . '/../models/Hall.php';

class HallService
{
    private $hallModel;

    public function __construct($hallModel)
    {
        $this->hallModel = $hallModel;
    }

    public function getAllHalls($cinemaId = null)
    {
        if ($cinemaId!==null && $cinemaId!=='') $this->hallModel->assertCinemaActive((int)$cinemaId);
        return $this->hallModel->getAllHalls($cinemaId);
    }

    public function getHallById($hallId)
    {
        if (empty($hallId) || !is_numeric($hallId)) {
            throw new InvalidArgumentException("Hall ID không hợp lệ.");
        }

        return $this->hallModel->getHallById($hallId);
    }

    public function getHallsByCinema($cinemaId)
    {
        if (empty($cinemaId) || !is_numeric($cinemaId)) {
            throw new InvalidArgumentException("Cinema ID không hợp lệ.");
        }

        return $this->hallModel->getHallsByCinema($cinemaId);
    }

    public function createHall($cinemaId, $name, $statusId, $seatCount = 0)
    {
        $this->hallModel->assertCinemaActive((int)$cinemaId);
        if (empty($cinemaId) || !is_numeric($cinemaId)) {
            throw new InvalidArgumentException("Cinema ID không hợp lệ.");
        }

        if (empty($name) || trim($name) === '') {
            throw new InvalidArgumentException("Tên phòng chiếu không được để trống.");
        }

        if (strlen($name) > 50) {
            throw new InvalidArgumentException("Tên phòng chiếu không được vượt quá 50 ký tự.");
        }

        if ($statusId===null || $statusId==='' || !in_array((int)$statusId,[0,1,2],true) || !is_numeric($statusId)) {
            throw new InvalidArgumentException("Status ID không hợp lệ.");
        }

        if ($seatCount === null || $seatCount === '') {
            $seatCount = 0;
        }
        if (!is_numeric($seatCount) || (int)$seatCount < 0 || (int)$seatCount > 500) {
            throw new InvalidArgumentException("Số lượng ghế không hợp lệ (0-500).");
        }

        return $this->hallModel->createHall($cinemaId, $name, $statusId, (int)$seatCount);
    }

    public function updateHall($hallId, $cinemaId, $name, $statusId)
    {
        $hall=$this->getHallById($hallId);
        if (!$hall || (int)$hall['status']!==1 || (int)$hall['CinemaStatus']!==1) throw new RuntimeException('Phòng hoặc rạp đã ngừng hoạt động: chỉ được mở lại.');
        $this->hallModel->assertCinemaActive((int)$cinemaId);
        if (empty($hallId) || !is_numeric($hallId)) {
            throw new InvalidArgumentException("Hall ID không hợp lệ.");
        }

        if (empty($cinemaId) || !is_numeric($cinemaId)) {
            throw new InvalidArgumentException("Cinema ID không hợp lệ.");
        }

        if (empty($name) || trim($name) === '') {
            throw new InvalidArgumentException("Tên phòng chiếu không được để trống.");
        }

        if (strlen($name) > 50) {
            throw new InvalidArgumentException("Tên phòng chiếu không được vượt quá 50 ký tự.");
        }

        if ($statusId===null || $statusId==='' || !in_array((int)$statusId,[0,1,2],true) || !is_numeric($statusId)) {
            throw new InvalidArgumentException("Status ID không hợp lệ.");
        }

        return $this->hallModel->updateHall($hallId, $cinemaId, $name, $statusId);
    }

    public function deleteHall($hallId)
    {
        $hall=$this->getHallById($hallId);
        if (!$hall || (int)$hall['status']!==1 || (int)$hall['CinemaStatus']!==1) throw new RuntimeException('Phòng hoặc rạp đã ngừng hoạt động: chỉ được mở lại.');
        if (empty($hallId) || !is_numeric($hallId)) {
            throw new InvalidArgumentException("Hall ID không hợp lệ.");
        }

        return $this->hallModel->deleteHall($hallId);
    }

    public function getSeatCount($hallId)
    {
        if (empty($hallId) || !is_numeric($hallId)) {
            throw new InvalidArgumentException("Hall ID không hợp lệ.");
        }

        return $this->hallModel->getSeatCount($hallId);
    }

    public function reopenHall(int $id): bool { return $this->hallModel->reopenHall($id); }
}

