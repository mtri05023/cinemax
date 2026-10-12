<?php
class Seat
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getSeatsByHall($hallId)
    {
        $sql = "SELECT 
                    s.seat_id      AS SeatID,
                    s.hall_id      AS HallID,
                    s.seat_type_id AS SeatTypeID,
                    s.row_name     AS RowName,
                    s.seat_number  AS SeatNumber,
                    s.status       AS Status,
                    st.type_name   AS TypeName,
                    st.price_multiplier AS PriceMultiplier
                FROM seats s 
                INNER JOIN seat_types st ON s.seat_type_id = st.seat_type_id 
                WHERE s.hall_id = ? AND s.status = 1 
                ORDER BY s.row_name, s.seat_number";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Prepare Error: " . $this->conn->error);
        }
        
        $stmt->bind_param("i", $hallId);
        if (!$stmt->execute()) {
            throw new Exception("SQL Execute Error: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception("SQL Get Result Error: " . $stmt->error);
        }

        $seats = [];
        while ($row = $result->fetch_assoc()) {
            $seats[] = $row;
        }

        return $seats;
    }

    public function getSeatById($seatId)
    {
        $sql = "SELECT 
                    s.seat_id      AS SeatID,
                    s.hall_id      AS HallID,
                    s.seat_type_id AS SeatTypeID,
                    s.row_name     AS RowName,
                    s.seat_number  AS SeatNumber,
                    s.status       AS Status,
                    st.type_name   AS TypeName,
                    st.price_multiplier AS PriceMultiplier
                FROM seats s 
                INNER JOIN seat_types st ON s.seat_type_id = st.seat_type_id 
                WHERE s.seat_id = ? AND s.status = 1";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Prepare Error: " . $this->conn->error);
        }
        
        $stmt->bind_param("i", $seatId);
        if (!$stmt->execute()) {
            throw new Exception("SQL Execute Error: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception("SQL Get Result Error: " . $stmt->error);
        }

        return $result->fetch_assoc();
    }

    public function createSeat($hallId, $seatTypeId, $rowName, $seatNumber)
    {
        $sql = "INSERT INTO seats (hall_id, seat_type_id, row_name, seat_number, status) VALUES (?, ?, ?, ?, 1)";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Prepare Error: " . $this->conn->error);
        }
        
        $stmt->bind_param("iisi", $hallId, $seatTypeId, $rowName, $seatNumber);
        if (!$stmt->execute()) {
            // Kiểm tra nếu là lỗi duplicate
            if ($this->conn->errno == 1062) {
                throw new Exception("Ghế này đã tồn tại trong phòng chiếu.");
            }
            throw new Exception("SQL Execute Error: " . $stmt->error);
        }

        return $this->conn->insert_id;
    }

    public function updateSeat($seatId, $seatTypeId)
    {
        $sql = "UPDATE seats SET seat_type_id = ? WHERE seat_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Prepare Error: " . $this->conn->error);
        }
        
        $stmt->bind_param("ii", $seatTypeId, $seatId);
        if (!$stmt->execute()) {
            throw new Exception("SQL Execute Error: " . $stmt->error);
        }

        return $stmt->affected_rows > 0;
    }

    public function deleteSeat($seatId)
    {
        // Xóa thật khỏi DB
        $sql = "DELETE FROM seats WHERE seat_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Prepare Error: " . $this->conn->error);
        }
        
        $stmt->bind_param("i", $seatId);
        if (!$stmt->execute()) {
            throw new Exception("SQL Execute Error: " . $stmt->error);
        }

        return $stmt->affected_rows > 0;
    }

    public function deleteAllSeatsByHall($hallId)
    {
        // Xóa thật tất cả ghế trong phòng
        $sql = "DELETE FROM seats WHERE hall_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Prepare Error: " . $this->conn->error);
        }
        
        $stmt->bind_param("i", $hallId);
        if (!$stmt->execute()) {
            throw new Exception("SQL Execute Error: " . $stmt->error);
        }

        return $stmt->affected_rows;
    }

    public function createBulkSeats($hallId, $seats)
    {
        // Tạo nhiều ghế cùng lúc
        $sql = "INSERT INTO seats (hall_id, seat_type_id, row_name, seat_number, status) VALUES (?, ?, ?, ?, 1)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Prepare Error: " . $this->conn->error);
        }

        $successCount = 0;
        $errors = [];

        foreach ($seats as $seat) {
            try {
                $stmt->bind_param("iisi", $hallId, $seat['seat_type_id'], $seat['row_name'], $seat['seat_number']);
                if ($stmt->execute()) {
                    $successCount++;
                } else {
                    // Bỏ qua duplicate, chỉ log lỗi khác
                    if ($this->conn->errno != 1062) {
                        $errors[] = "Lỗi khi tạo ghế {$seat['row_name']}{$seat['seat_number']}: " . $stmt->error;
                    }
                }
            } catch (Exception $e) {
                $errors[] = "Lỗi khi tạo ghế {$seat['row_name']}{$seat['seat_number']}: " . $e->getMessage();
            }
        }

        return [
            'success_count' => $successCount,
            'errors' => $errors
        ];
    }
}
