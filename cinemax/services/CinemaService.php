<?php
require_once __DIR__ . '/../models/Cinema.php';

class CinemaService
{
    private $cinemaModel;

    public function __construct($cinemaModel)
    {
        $this->cinemaModel = $cinemaModel;
    }

    public function getAllCinemas(bool $includeInactive = false)
    {
        return $this->cinemaModel->getAllCinemas($includeInactive);
    }

    public function getCinemaById($cinemaId)
    {
        if (empty($cinemaId) || !is_numeric($cinemaId)) {
            throw new InvalidArgumentException("Cinema ID không hợp lệ.");
        }

        return $this->cinemaModel->getCinemaById($cinemaId, true);
    }

    public function createCinema($name, $address, $locationId, $statusId)
    {
        if (empty($name) || trim($name) === '') {
            throw new InvalidArgumentException("Tên rạp chiếu không được để trống.");
        }

        if (strlen($name) > 150) {
            throw new InvalidArgumentException("Tên rạp chiếu không được vượt quá 150 ký tự.");
        }

        if (empty($address) || trim($address) === '') {
            throw new InvalidArgumentException("Địa chỉ không được để trống.");
        }

        if (!is_numeric($locationId)) {
            throw new InvalidArgumentException("Location ID không hợp lệ.");
        }

        if ($statusId === '' || !is_numeric($statusId)) {
            throw new InvalidArgumentException("Status ID không hợp lệ.");
        }

        return $this->cinemaModel->createCinema($name, $address, (int)$locationId, (int)$statusId);
    }

    public function updateCinema($cinemaId, $name, $address, $locationId, $statusId)
    {
        $existing=$this->getCinemaById($cinemaId);
        if (!$existing || (int)$existing['Status']!==1) throw new RuntimeException('Rạp đã ngừng hoạt động: chỉ được mở lại rạp.');
        if (!is_numeric($cinemaId)) {
            throw new InvalidArgumentException("Cinema ID không hợp lệ.");
        }

        if (empty($name) || trim($name) === '') {
            throw new InvalidArgumentException("Tên rạp chiếu không được để trống.");
        }

        if (strlen($name) > 150) {
            throw new InvalidArgumentException("Tên rạp chiếu không được vượt quá 150 ký tự.");
        }

        if (empty($address) || trim($address) === '') {
            throw new InvalidArgumentException("Địa chỉ không được để trống.");
        }

        if (!is_numeric($locationId)) {
            throw new InvalidArgumentException("Location ID không hợp lệ.");
        }

        if ($statusId === '' || !is_numeric($statusId)) {
            throw new InvalidArgumentException("Status ID không hợp lệ.");
        }

        return $this->cinemaModel->updateCinema((int)$cinemaId, $name, $address, (int)$locationId, (int)$statusId);
    }

    public function deleteCinema($cinemaId)
    {
        $cinema=$this->getCinemaById($cinemaId);
        if (!$cinema || (int)$cinema['Status']!==1) throw new RuntimeException('Rạp đã ngừng hoạt động: chỉ được mở lại rạp.');
        if (empty($cinemaId) || !is_numeric($cinemaId)) {
            throw new InvalidArgumentException("Cinema ID không hợp lệ.");
        }

        return $this->cinemaModel->deleteCinema($cinemaId);
    }

    public function reopenCinema(int $id): bool { return $this->cinemaModel->reopenCinema($id); }

    public function getAllLocations()
    {
        return $this->cinemaModel->getAllLocations();
    }

    public function getAllCinemaStatuses()
    {
        return $this->cinemaModel->getAllCinemaStatuses();
    }
}
