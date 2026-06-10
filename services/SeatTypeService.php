<?php
require_once __DIR__ . '/../models/SeatType.php';

class SeatTypeService
{
    private $seatTypeModel;

    public function __construct($seatTypeModel)
    {
        $this->seatTypeModel = $seatTypeModel;
    }

    public function getAllSeatTypes()
    {
        return $this->seatTypeModel->getAllSeatTypes();
    }

    public function getSeatTypeById($seatTypeId)
    {
        if (empty($seatTypeId) || !is_numeric($seatTypeId)) {
            throw new InvalidArgumentException("Seat Type ID không hợp lệ.");
        }

        return $this->seatTypeModel->getSeatTypeById($seatTypeId);
    }

    public function getSeatTypeByName($typeName)
    {
        if (empty($typeName) || trim($typeName) === '') {
            throw new InvalidArgumentException("Tên loại ghế không được để trống.");
        }

        return $this->seatTypeModel->getSeatTypeByName($typeName);
    }

    public function createSeatType($typeName, $priceMultiplier)
    {
        if (empty($typeName) || trim($typeName) === '') {
            throw new InvalidArgumentException("Tên loại ghế không được để trống.");
        }

        if (strlen($typeName) > 50) {
            throw new InvalidArgumentException("Tên loại ghế không được vượt quá 50 ký tự.");
        }

        if (!is_numeric($priceMultiplier) || $priceMultiplier < 0) {
            throw new InvalidArgumentException("Hệ số giá phải là số dương.");
        }

        // Kiểm tra xem loại ghế đã tồn tại chưa
        $existingSeatType = $this->seatTypeModel->getSeatTypeByName($typeName);
        if ($existingSeatType) {
            throw new InvalidArgumentException("Loại ghế này đã tồn tại.");
        }

        return $this->seatTypeModel->createSeatType($typeName, $priceMultiplier);
    }

    public function updateSeatType($seatTypeId, $typeName, $priceMultiplier)
    {
        if (empty($seatTypeId) || !is_numeric($seatTypeId)) {
            throw new InvalidArgumentException("Seat Type ID không hợp lệ.");
        }

        if (empty($typeName) || trim($typeName) === '') {
            throw new InvalidArgumentException("Tên loại ghế không được để trống.");
        }

        if (strlen($typeName) > 50) {
            throw new InvalidArgumentException("Tên loại ghế không được vượt quá 50 ký tự.");
        }

        if (!is_numeric($priceMultiplier) || $priceMultiplier < 0) {
            throw new InvalidArgumentException("Hệ số giá phải là số dương.");
        }

        // Kiểm tra xem loại ghế đã tồn tại chưa (trừ chính nó)
        $existingSeatType = $this->seatTypeModel->getSeatTypeByName($typeName);
        $existingSeatTypeId = $existingSeatType['SeatTypeID'] ?? $existingSeatType['seat_type_id'] ?? null;
        if ($existingSeatType && $existingSeatTypeId != $seatTypeId) {
            throw new InvalidArgumentException("Loại ghế này đã tồn tại.");
        }

        return $this->seatTypeModel->updateSeatType($seatTypeId, $typeName, $priceMultiplier);
    }

    public function deleteSeatType($seatTypeId)
    {
        if (empty($seatTypeId) || !is_numeric($seatTypeId)) {
            throw new InvalidArgumentException("Seat Type ID không hợp lệ.");
        }

        return $this->seatTypeModel->deleteSeatType($seatTypeId);
    }
}

