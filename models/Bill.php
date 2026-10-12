<?php

class Bill
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Lấy tất cả bills với thông tin user
     */
    public function getAllBills()
    {
        $sql = "SELECT b.*, u.full_name, u.email, u.phone
                FROM bills b
                JOIN users u ON b.user_id = u.user_id
                ORDER BY b.created_at DESC";
        $result = $this->conn->query($sql);

        $bills = [];
        while ($row = $result->fetch_assoc()) {
            $bills[] = $row;
        }

        return $bills;
    }

    /**
     * Lấy bill theo ID
     */
    public function getBillById($billId)
    {
        $sql = "SELECT b.*, u.full_name, u.email, u.phone
                FROM bills b
                JOIN users u ON b.user_id = u.user_id
                WHERE b.bill_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $billId);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    /**
     * Lấy bills phân trang với filter
     */
    public function getPaginated($page = 1, $limit = 10, $status = null, $search = null)
    {
        $offset = ($page - 1) * $limit;

        $sql = "SELECT b.*, u.full_name, u.email, u.phone
                FROM bills b
                JOIN users u ON b.user_id = u.user_id
                WHERE 1=1";
        $params = [];
        $types = "";

        if ($status !== null && $status !== '') {
            $sql .= " AND b.status = ?";
            $params[] = $status;
            $types .= "s";
        }

        if ($search !== null && $search !== '') {
            $sql .= " AND (b.bill_id LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "sss";
        }

        $sql .= " ORDER BY b.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $bills = [];
        while ($row = $result->fetch_assoc()) {
            $bills[] = $row;
        }

        return $bills;
    }

    /**
     * Đếm tổng số bills theo filter
     */
    public function getTotalCount($status = null, $search = null)
    {
        $sql = "SELECT COUNT(*) as total
                FROM bills b
                JOIN users u ON b.user_id = u.user_id
                WHERE 1=1";
        $params = [];
        $types = "";

        if ($status !== null && $status !== '') {
            $sql .= " AND b.status = ?";
            $params[] = $status;
            $types .= "s";
        }

        if ($search !== null && $search !== '') {
            $sql .= " AND (b.bill_id LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "sss";
        }

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return (int)$row['total'];
    }

    /**
     * Đếm số bills theo từng status
     */
    public function getCountByStatus()
    {
        $sql = "SELECT status, COUNT(*) as count FROM bills GROUP BY status";
        $result = $this->conn->query($sql);

        $counts = [
            'pending' => 0,
            'paid' => 0,
            'cancelled' => 0,
            'refunded' => 0,
            'total' => 0
        ];

        while ($row = $result->fetch_assoc()) {
            $counts[$row['status']] = (int)$row['count'];
            $counts['total'] += (int)$row['count'];
        }

        return $counts;
    }

    /**
     * Cập nhật status của bill
     */
    public function updateStatus($billId, $status)
    {
        $validStatuses = ['pending', 'paid', 'cancelled', 'refunded'];
        if (!in_array($status, $validStatuses)) {
            throw new InvalidArgumentException("Invalid status: $status");
        }

        $sql = "UPDATE bills SET status = ?";

        // Nếu chuyển sang paid thì cập nhật paid_at
        if ($status === 'paid') {
            $sql .= ", paid_at = CURRENT_TIMESTAMP";
        }

        $sql .= " WHERE bill_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $status, $billId);

        return $stmt->execute();
    }

    /**
     * Lấy chi tiết tickets của một bill
     */
    public function getTicketsByBillId($billId)
    {
        $sql = "SELECT t.*, s.show_date, s.start_time, m.title as movie_title, 
                       h.name as hall_name, c.name as cinema_name
                FROM tickets t
                JOIN shows s ON t.show_id = s.show_id
                JOIN movies m ON s.movie_id = m.movie_id
                JOIN halls h ON s.hall_id = h.hall_id
                JOIN cinemas c ON h.cinema_id = c.cinema_id
                WHERE t.bill_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $billId);
        $stmt->execute();
        $result = $stmt->get_result();

        $tickets = [];
        while ($row = $result->fetch_assoc()) {
            $tickets[] = $row;
        }

        return $tickets;
    }

    /**
     * Lấy combos của một bill
     */
    public function getCombosByBillId($billId)
    {
        $sql = "SELECT bc.*, cb.name as combo_name
                FROM bill_combos bc
                JOIN combos cb ON bc.combo_id = cb.combo_id
                WHERE bc.bill_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $billId);
        $stmt->execute();
        $result = $stmt->get_result();

        $combos = [];
        while ($row = $result->fetch_assoc()) {
            $combos[] = $row;
        }

        return $combos;
    }

    public function insertBillCombos($billId, $combos)
    {
        $sql = "INSERT INTO bill_combos (bill_id, combo_id, quantity, price) 
            VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);

        foreach ($combos as $combo) {
            $comboId = $combo['combo_id'];
            $quantity = $combo['quantity'];
            $price = $combo['price'];

            $stmt->bind_param("iiid", $billId, $comboId, $quantity, $price);
            $stmt->execute();
        }
    }

    public function getBillsByUserId($userId)
    {
        $sql = "SELECT b.*,
                       COUNT(t.ticket_id) as total_tickets
                FROM bills b
                LEFT JOIN tickets t ON t.bill_id = b.bill_id
                WHERE b.user_id = ?
                GROUP BY b.bill_id
                ORDER BY b.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
