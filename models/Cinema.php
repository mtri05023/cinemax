<?php
class Cinema
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getAllCinemas(bool $includeInactive = false)
    {
        $sql = "SELECT 
                    c.cinema_id   AS CinemaID,
                    c.name        AS Name,
                    c.address     AS Address,
                    c.location_id AS LocationID,
                    c.status      AS Status,
                    c.created_at  AS CreatedAt,
                    l.name        AS LocationName,
                    (SELECT COUNT(*) 
                     FROM halls h 
                     WHERE h.cinema_id = c.cinema_id 
                       AND h.status = 1) AS HallCount
                FROM cinemas c 
                INNER JOIN locations l ON c.location_id = l.location_id " .
                ($includeInactive ? "" : " WHERE c.status = 1 ") .
                " ORDER BY c.cinema_id DESC";
        
        $result = $this->conn->query($sql);
        if (!$result) {
            throw new Exception("SQL Error: " . $this->conn->error);
        }

        $cinemas = [];
        while ($row = $result->fetch_assoc()) {
            $cinemas[] = $row;
        }

        return $cinemas;
    }

    public function getCinemaById($cinemaId, bool $includeInactive = false)
    {
        $sql = "SELECT 
                    c.cinema_id   AS CinemaID,
                    c.name        AS Name,
                    c.address     AS Address,
                    c.location_id AS LocationID,
                    c.status      AS Status,
                    c.created_at  AS CreatedAt,
                    l.name        AS LocationName,
                    (SELECT COUNT(*) 
                     FROM halls h 
                     WHERE h.cinema_id = c.cinema_id 
                       AND h.status = 1) AS HallCount
                FROM cinemas c 
                INNER JOIN locations l ON c.location_id = l.location_id 
                WHERE c.cinema_id = ? " . ($includeInactive ? "" : "AND c.status = 1");
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Prepare Error: " . $this->conn->error);
        }
        
        $stmt->bind_param("i", $cinemaId);
        if (!$stmt->execute()) {
            throw new Exception("SQL Execute Error: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        if (!$result) {
            throw new Exception("SQL Get Result Error: " . $stmt->error);
        }

        return $result->fetch_assoc();
    }

    public function createCinema($name, $address, $locationId, $statusId = null)
    {
        $status = ($statusId === null || $statusId === '') ? 1 : (int)$statusId;
        $sql = "INSERT INTO cinemas (name, address, location_id, status) VALUES (?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Prepare Error: " . $this->conn->error);
        }
        
        $stmt->bind_param("ssii", $name, $address, $locationId, $status);
        if (!$stmt->execute()) {
            throw new Exception("SQL Execute Error: " . $stmt->error);
        }

        return $this->conn->insert_id;
    }

    public function updateCinema($cinemaId, $name, $address, $locationId, $statusId = null)
    {
        $status = ($statusId === null || $statusId === '') ? null : (int)$statusId;

        if ($status === null) {
            $sql = "UPDATE cinemas SET name = ?, address = ?, location_id = ? WHERE cinema_id = ?";
        } else {
            $sql = "UPDATE cinemas SET name = ?, address = ?, location_id = ?, status = ? WHERE cinema_id = ?";
        }
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Prepare Error: " . $this->conn->error);
        }
        
        if ($status === null) {
            $stmt->bind_param("ssii", $name, $address, $locationId, $cinemaId);
        } else {
            $stmt->bind_param("ssiii", $name, $address, $locationId, $status, $cinemaId);
        }
        if (!$stmt->execute()) {
            throw new Exception("SQL Execute Error: " . $stmt->error);
        }

        // Đồng bộ trạng thái phòng chiếu khi đóng/mở rạp
        if ($status === 0 || $status === 1) {
            $hallSql = "UPDATE halls SET status = ? WHERE cinema_id = ?";
            $hallStmt = $this->conn->prepare($hallSql);
            if ($hallStmt) {
                $hallStmt->bind_param("ii", $status, $cinemaId);
                $hallStmt->execute();
            }
        }

        return true;
    }

    public function deleteCinema($cinemaId)
    {
        // Soft delete: đổi status = 0 và đóng luôn các phòng chiếu
        $sql = "UPDATE cinemas SET status = 0 WHERE cinema_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Prepare Error: " . $this->conn->error);
        }
        
        $stmt->bind_param("i", $cinemaId);
        if (!$stmt->execute()) {
            throw new Exception("SQL Execute Error: " . $stmt->error);
        }

        // Đóng tất cả phòng chiếu thuộc rạp
        $hallSql = "UPDATE halls SET status = 0 WHERE cinema_id = ?";
        $hallStmt = $this->conn->prepare($hallSql);
        if ($hallStmt) {
            $hallStmt->bind_param("i", $cinemaId);
            $hallStmt->execute();
        }

        return true;
    }

    public function getAllLocations()
    {
        $sql = "SELECT location_id as LocationID, name as Name FROM locations ORDER BY name";
        $result = $this->conn->query($sql);
        if (!$result) {
            throw new Exception("SQL Error: " . $this->conn->error);
        }

        $locations = [];
        while ($row = $result->fetch_assoc()) {
            $locations[] = $row;
        }

        return $locations;
    }

    /**
     * Danh sách trạng thái rạp chiếu.
     * Vì schema hiện tại chỉ có cột status (TINYINT) trong bảng cinemas,
     * nên ta map cố định sang danh sách StatusID / StatusName để dùng cho UI.
     */
    public function getAllCinemaStatuses()
    {
        return [
            [
                'StatusID'   => 1,
                'StatusName' => 'Đang hoạt động',
            ],
            [
                'StatusID'   => 0,
                'StatusName' => 'Ngừng hoạt động',
            ],
        ];
    }
}
