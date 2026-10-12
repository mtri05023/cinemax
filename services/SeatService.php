<?php
require_once __DIR__ . '/../models/Seat.php';

class SeatService
{
    private $seatModel;

    public function __construct($seatModel)
    {
        $this->seatModel = $seatModel;
    }

    public function getSeatsByHall($hallId)
    {
        if (empty($hallId) || !is_numeric($hallId)) {
            throw new InvalidArgumentException("Hall ID không hợp lệ.");
        }

        return $this->seatModel->getSeatsByHall($hallId);
    }

    public function getSeatById($seatId)
    {
        if (empty($seatId) || !is_numeric($seatId)) {
            throw new InvalidArgumentException("Seat ID không hợp lệ.");
        }

        return $this->seatModel->getSeatById($seatId);
    }

    public function createSeat($hallId, $seatTypeId, $rowName, $seatNumber)
    {
        if (empty($hallId) || !is_numeric($hallId)) {
            throw new InvalidArgumentException("Hall ID không hợp lệ.");
        }

        if (empty($seatTypeId) || !is_numeric($seatTypeId)) {
            throw new InvalidArgumentException("Seat Type ID không hợp lệ.");
        }

        if (empty($rowName) || strlen($rowName) > 2) {
            throw new InvalidArgumentException("Tên hàng ghế không hợp lệ (tối đa 2 ký tự).");
        }

        if (empty($seatNumber) || !is_numeric($seatNumber) || $seatNumber < 1) {
            throw new InvalidArgumentException("Số ghế không hợp lệ (phải là số dương).");
        }

        return $this->seatModel->createSeat($hallId, $seatTypeId, $rowName, $seatNumber);
    }

    public function updateSeat($seatId, $seatTypeId)
    {
        if (empty($seatId) || !is_numeric($seatId)) {
            throw new InvalidArgumentException("Seat ID không hợp lệ.");
        }

        if (empty($seatTypeId) || !is_numeric($seatTypeId)) {
            throw new InvalidArgumentException("Seat Type ID không hợp lệ.");
        }

        return $this->seatModel->updateSeat($seatId, $seatTypeId);
    }

    public function deleteSeat($seatId)
    {
        if (empty($seatId) || !is_numeric($seatId)) {
            throw new InvalidArgumentException("Seat ID không hợp lệ.");
        }

        return $this->seatModel->deleteSeat($seatId);
    }

    public function deleteAllSeatsByHall($hallId)
    {
        if (empty($hallId) || !is_numeric($hallId)) {
            throw new InvalidArgumentException("Hall ID không hợp lệ.");
        }

        return $this->seatModel->deleteAllSeatsByHall($hallId);
    }

    public function createBulkSeats($hallId, $seats)
    {
        if (empty($hallId) || !is_numeric($hallId)) {
            throw new InvalidArgumentException("Hall ID không hợp lệ.");
        }

        if (empty($seats) || !is_array($seats)) {
            throw new InvalidArgumentException("Danh sách ghế không hợp lệ.");
        }

        // Validate từng ghế
        foreach ($seats as $seat) {
            if (empty($seat['seat_type_id']) || !is_numeric($seat['seat_type_id'])) {
                throw new InvalidArgumentException("Seat Type ID không hợp lệ.");
            }
            if (empty($seat['row_name']) || strlen($seat['row_name']) > 2) {
                throw new InvalidArgumentException("Tên hàng ghế không hợp lệ.");
            }
            if (empty($seat['seat_number']) || !is_numeric($seat['seat_number']) || $seat['seat_number'] < 1) {
                throw new InvalidArgumentException("Số ghế không hợp lệ.");
            }
        }

        return $this->seatModel->createBulkSeats($hallId, $seats);
    }
}
