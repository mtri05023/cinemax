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
        if (empty($cinemaId) || !is_numeric($cinemaId)) {
            throw new InvalidArgumentException("Cinema ID không hợp lệ.");
        }

        if (empty($name) || trim($name) === '') {
            throw new InvalidArgumentException("Tên phòng chiếu không được để trống.");
        }

        if (strlen($name) > 50) {
            throw new InvalidArgumentException("Tên phòng chiếu không được vượt quá 50 ký tự.");
        }

        if (empty($statusId) || !is_numeric($statusId)) {
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

        if (empty($statusId) || !is_numeric($statusId)) {
            throw new InvalidArgumentException("Status ID không hợp lệ.");
        }

        return $this->hallModel->updateHall($hallId, $cinemaId, $name, $statusId);
    }

    public function deleteHall($hallId)
    {
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
}

