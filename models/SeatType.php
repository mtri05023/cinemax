<?php
class SeatType
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getAllSeatTypes()
    {
        $sql = "SELECT seat_type_id as SeatTypeID, type_name as TypeName, price_multiplier as PriceMultiplier, status as Status FROM seat_types WHERE status = 1 ORDER BY seat_type_id";
        $result = $this->conn->query($sql);
        if (!$result) {
            throw new Exception("SQL Error: " . $this->conn->error);
        }

        $seatTypes = [];
        while ($row = $result->fetch_assoc()) {
            $seatTypes[] = $row;
        }

        return $seatTypes;
    }

    public function getSeatTypeById($seatTypeId)
    {
        $sql = "SELECT seat_type_id as SeatTypeID, type_name as TypeName, price_multiplier as PriceMultiplier, status as Status FROM seat_types WHERE seat_type_id = ? AND status = 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Error: " . $this->conn->error);
        }
        $stmt->bind_param("i", $seatTypeId);
        if (!$stmt->execute()) {
            throw new Exception("SQL Execute Error: " . $stmt->error);
        }
        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception("SQL Get Result Error: " . $stmt->error);
        }
        return $result->fetch_assoc();
    }

    public function getSeatTypeByName($typeName)
    {
        $sql = "SELECT seat_type_id as SeatTypeID, type_name as TypeName, price_multiplier as PriceMultiplier, status as Status FROM seat_types WHERE type_name = ? AND status = 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Error: " . $this->conn->error);
        }
        $stmt->bind_param("s", $typeName);
        if (!$stmt->execute()) {
            throw new Exception("SQL Execute Error: " . $stmt->error);
        }
        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception("SQL Get Result Error: " . $stmt->error);
        }
        return $result->fetch_assoc();
    }

    public function createSeatType($typeName, $priceMultiplier)
    {
        $sql = "INSERT INTO seat_types (type_name, price_multiplier, status) VALUES (?, ?, 1)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Error: " . $this->conn->error);
        }
        $stmt->bind_param("sd", $typeName, $priceMultiplier);

        return $stmt->execute();
    }

    public function updateSeatType($seatTypeId, $typeName, $priceMultiplier)
    {
        $sql = "UPDATE seat_types SET type_name = ?, price_multiplier = ? WHERE seat_type_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Error: " . $this->conn->error);
        }
        $stmt->bind_param("sdi", $typeName, $priceMultiplier, $seatTypeId);

        return $stmt->execute();
    }

    public function deleteSeatType($seatTypeId)
    {
        // Kiểm tra xem có ghế nào đang sử dụng loại ghế này không
        $checkSql = "SELECT COUNT(*) as count FROM seats WHERE seat_type_id = ?";
        $checkStmt = $this->conn->prepare($checkSql);
        if (!$checkStmt) {
            throw new Exception("SQL Error: " . $this->conn->error);
        }
        $checkStmt->bind_param("i", $seatTypeId);
        if (!$checkStmt->execute()) {
            throw new Exception("SQL Execute Error: " . $checkStmt->error);
        }
        $result = $checkStmt->get_result();
        if (!$result) {
            throw new Exception("SQL Get Result Error: " . $checkStmt->error);
        }
        $row = $result->fetch_assoc();

        if ($row['count'] > 0) {
            throw new Exception("Không thể xóa loại ghế này vì đang có ghế đang sử dụng.");
        }

        // Xóa thật khỏi DB
        $sql = "DELETE FROM seat_types WHERE seat_type_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Error: " . $this->conn->error);
        }
        $stmt->bind_param("i", $seatTypeId);

        return $stmt->execute();
    }
}

