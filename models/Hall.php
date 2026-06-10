<?php
class Hall
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getAllHalls($cinemaId = null)
    {
        if ($cinemaId) {
            $sql = "SELECT 
                        h.*,
                        h.hall_id AS HallID,
                        h.name    AS Name,
                        c.name    AS CinemaName,
                    CASE 
                        WHEN h.status = 1 THEN 'Đang hoạt động'
                        WHEN h.status = 0 THEN 'Tạm dừng'
                        ELSE 'Bảo trì'
                    END as StatusName
                    FROM halls h 
                    INNER JOIN cinemas c ON h.cinema_id = c.cinema_id 
                    WHERE h.cinema_id = ? 
                    ORDER BY h.hall_id DESC";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("SQL Error: " . $this->conn->error);
            }
            $stmt->bind_param("i", $cinemaId);
            if (!$stmt->execute()) {
                throw new Exception("SQL Execute Error: " . $stmt->error);
            }
            $result = $stmt->get_result();
            if (!$result) {
                throw new Exception("SQL Get Result Error: " . $stmt->error);
            }
        } else {
            $sql = "SELECT 
                        h.*,
                        h.hall_id AS HallID,
                        h.name    AS Name,
                        c.name    AS CinemaName,
                    CASE 
                        WHEN h.status = 1 THEN 'Đang hoạt động'
                        WHEN h.status = 0 THEN 'Tạm dừng'
                        ELSE 'Bảo trì'
                    END as StatusName
                    FROM halls h 
                    INNER JOIN cinemas c ON h.cinema_id = c.cinema_id 
                    WHERE 1=1
                    ORDER BY h.hall_id DESC";
            $result = $this->conn->query($sql);
            if (!$result) {
                throw new Exception("SQL Error: " . $this->conn->error);
            }
        }

        $halls = [];
        while ($row = $result->fetch_assoc()) {
            $halls[] = $row;
        }

        return $halls;
    }

    public function getHallById($hallId)
    {
        $sql = "SELECT 
                    h.*,
                    h.hall_id AS HallID,
                    h.name    AS Name,
                    c.name    AS CinemaName,
                CASE 
                    WHEN h.status = 1 THEN 'Đang hoạt động'
                    WHEN h.status = 0 THEN 'Tạm dừng'
                    ELSE 'Bảo trì'
                END as StatusName
                FROM halls h 
                INNER JOIN cinemas c ON h.cinema_id = c.cinema_id 
                WHERE h.hall_id = ? AND h.status = 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Error: " . $this->conn->error);
        }
        $stmt->bind_param("i", $hallId);
        if (!$stmt->execute()) {
            throw new Exception("SQL Execute Error: " . $stmt->error);
        }
        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception("SQL Get Result Error: " . $stmt->error);
        }
        return $result->fetch_assoc();
    }

    public function getHallsByCinema($cinemaId)
    {
        $sql = "SELECT h.*,
                CASE 
                    WHEN h.status = 1 THEN 'Đang hoạt động'
                    WHEN h.status = 0 THEN 'Tạm dừng'
                    ELSE 'Bảo trì'
                END as StatusName
                FROM halls h 
                WHERE h.cinema_id = ? AND h.status = 1 
                ORDER BY h.name";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Error: " . $this->conn->error);
        }
        $stmt->bind_param("i", $cinemaId);
        if (!$stmt->execute()) {
            throw new Exception("SQL Execute Error: " . $stmt->error);
        }
        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception("SQL Get Result Error: " . $stmt->error);
        }
        $halls = [];
        while ($row = $result->fetch_assoc()) {
            $halls[] = $row;
        }

        return $halls;
    }

    public function createHall($cinemaId, $name, $statusId = 1, $seatCount = 0)
    {
        $this->conn->begin_transaction();
        try {
            $sql = "INSERT INTO halls (cinema_id, name, total_seats, status) VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("SQL Error: " . $this->conn->error);
            }
            $seatCountInt = (int)$seatCount;
            $stmt->bind_param("isii", $cinemaId, $name, $seatCountInt, $statusId);
            if (!$stmt->execute()) {
                throw new Exception("SQL Execute Error: " . $stmt->error);
            }

            $hallId = (int)$this->conn->insert_id;
            if ($hallId <= 0) {
                throw new Exception("Không tạo được phòng chiếu.");
            }

            $createdCount = 0;
            if ($seatCountInt > 0) {
                $defaultSeatTypeId = $this->getDefaultSeatTypeId();
                $createdCount = $this->createDefaultSeats($hallId, $defaultSeatTypeId, $seatCountInt);
            }

            $this->conn->commit();
            return [
                'hall_id' => $hallId,
                'seat_count' => $createdCount
            ];
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function updateHall($hallId, $cinemaId, $name, $statusId = 1)
    {
        $sql = "UPDATE halls SET cinema_id = ?, name = ?, status = ? WHERE hall_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Error: " . $this->conn->error);
        }
        $stmt->bind_param("isii", $cinemaId, $name, $statusId, $hallId);

        return $stmt->execute();
    }

    public function deleteHall($hallId)
    {
        // Xóa thật phòng chiếu khỏi DB
        $sql = "DELETE FROM halls WHERE hall_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Error: " . $this->conn->error);
        }
        $stmt->bind_param("i", $hallId);

        return $stmt->execute();
    }

    public function getSeatCount($hallId)
    {
        $sql = "SELECT COUNT(*) as count FROM seats WHERE hall_id = ? AND status = 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Error: " . $this->conn->error);
        }
        $stmt->bind_param("i", $hallId);
        if (!$stmt->execute()) {
            throw new Exception("SQL Execute Error: " . $stmt->error);
        }
        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception("SQL Get Result Error: " . $stmt->error);
        }
        $row = $result->fetch_assoc();
        return $row['count'] ?? 0;
    }

    private function getDefaultSeatTypeId()
    {
        $sql = "SELECT seat_type_id FROM seat_types WHERE status = 1 ORDER BY seat_type_id ASC LIMIT 1";
        $result = $this->conn->query($sql);
        if (!$result) {
            throw new Exception("SQL Error (seat_types): " . $this->conn->error);
        }
        $row = $result->fetch_assoc();
        if (!$row || empty($row['seat_type_id'])) {
            throw new Exception("Chưa có loại ghế hoạt động để tạo sơ đồ mặc định.");
        }
        return (int)$row['seat_type_id'];
    }

    private function createDefaultSeats($hallId, $seatTypeId, $seatCount)
    {
        $rowsNeeded = (int)ceil($seatCount / 12);
        $maxRows = 26;
        if ($rowsNeeded > $maxRows) {
            $rowsNeeded = $maxRows;
        }
        $seatsPerRow = (int)ceil($seatCount / $rowsNeeded);

        $sql = "INSERT INTO seats (hall_id, seat_type_id, row_name, seat_number, status) VALUES (?, ?, ?, ?, 1)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Prepare Error (seats): " . $this->conn->error);
        }

        $created = 0;
        for ($row = 0; $row < $rowsNeeded; $row++) {
            $rowName = chr(65 + $row);
            for ($seatNum = 1; $seatNum <= $seatsPerRow; $seatNum++) {
                if ($created >= $seatCount) {
                    break 2;
                }
                $stmt->bind_param("iisi", $hallId, $seatTypeId, $rowName, $seatNum);
                if (!$stmt->execute()) {
                    throw new Exception("SQL Execute Error (seats): " . $stmt->error);
                }
                $created++;
            }
        }

        return $created;
    }
}

