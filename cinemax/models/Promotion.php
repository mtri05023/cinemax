<?php

class Promotion
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getAllPromotions($search = '')
    {
        $sql = "
            SELECT p.*,
                   (
                       SELECT COUNT(*)
                       FROM bills b
                       WHERE b.promotion_id = p.promotion_id
                         AND b.status IN ('pending', 'paid', 'refunded')
                   ) AS usage_count
            FROM promotions p
            WHERE p.status != -1
        ";
        $types = '';
        $params = [];

        $search = trim((string)$search);
        if ($search !== '') {
            $sql .= " AND (p.code LIKE ? OR p.name LIKE ?)";
            $types = 'ss';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $sql .= " ORDER BY p.promotion_id DESC";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function getPromotionById($promotionId)
    {
        $sql = "SELECT * FROM promotions WHERE promotion_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("i", $promotionId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc() ?: null;
    }

    public function getPromotionByCode($code, bool $forUpdate = false)
    {
        $sql = "SELECT * FROM promotions WHERE UPPER(code) = UPPER(?) LIMIT 1";
        if ($forUpdate) {
            $sql .= " FOR UPDATE";
        }
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc() ?: null;
    }

    public function countPromotionUsage(int $promotionId, bool $forUpdate = false): int
    {
        $sql = "
            SELECT bill_id
            FROM bills
            WHERE promotion_id = ?
              AND status IN ('pending', 'paid', 'refunded')
        ";
        if ($forUpdate) $sql .= ' FOR UPDATE';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param("i", $promotionId);
        $stmt->execute();
        return $stmt->get_result()->num_rows;
    }

    public function countUserUsage(int $promotionId, int $userId, bool $forUpdate = false): int
    {
        $stmt=$this->conn->prepare("SELECT bill_id FROM bills WHERE promotion_id=? AND user_id=? AND status IN ('pending','paid','refunded')".($forUpdate?' FOR UPDATE':''));
        $stmt->bind_param('ii',$promotionId,$userId); $stmt->execute();
        return $stmt->get_result()->num_rows;
    }

    public function hasPurchased(int $userId, bool $forUpdate = false): bool
    {
        $stmt=$this->conn->prepare("SELECT bill_id FROM bills WHERE user_id=? AND status IN ('paid','refunded') LIMIT 1".($forUpdate?' FOR UPDATE':''));
        $stmt->bind_param('i',$userId); $stmt->execute();
        return $stmt->get_result()->num_rows>0;
    }

    public function createPromotion(array $data)
    {
        $sql = "
            INSERT INTO promotions (
                code,
                name,
                discount_type,
                discount_value,
                discount_percent,
                start_date,
                end_date,
                min_amount,
                usage_limit,
                per_user_limit,
                audience,
                status,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $discountPercentCompat = $data['discount_type'] === 'percent'
            ? (float)$data['discount_value']
            : 0;

        $stmt->bind_param(
            "sssddssdiis",
            $data['code'],
            $data['name'],
            $data['discount_type'],
            $data['discount_value'],
            $discountPercentCompat,
            $data['start_date'],
            $data['end_date'],
            $data['min_amount'],
            $data['usage_limit'],
            $data['per_user_limit'],
            $data['audience']
        );

        return $stmt->execute() ? (int)$this->conn->insert_id : false;
    }

    public function updatePromotion(int $promotionId, array $data)
    {
        $sql = "
            UPDATE promotions
            SET
                code = ?,
                name = ?,
                discount_type = ?,
                discount_value = ?,
                discount_percent = ?,
                start_date = ?,
                end_date = ?,
                min_amount = ?,
                usage_limit = ?,
                per_user_limit = ?,
                audience = ?
            WHERE promotion_id = ?
        ";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $discountPercentCompat = $data['discount_type'] === 'percent'
            ? (float)$data['discount_value']
            : 0;

        $stmt->bind_param(
            "sssddssdiisi",
            $data['code'],
            $data['name'],
            $data['discount_type'],
            $data['discount_value'],
            $discountPercentCompat,
            $data['start_date'],
            $data['end_date'],
            $data['min_amount'],
            $data['usage_limit'],
            $data['per_user_limit'],
            $data['audience'],
            $promotionId
        );

        return $stmt->execute();
    }

    public function restorePromotion(int $promotionId, array $data): bool
    {
        $sql = "
            UPDATE promotions
            SET
                name = ?,
                discount_type = ?,
                discount_value = ?,
                discount_percent = ?,
                start_date = ?,
                end_date = ?,
                min_amount = ?,
                usage_limit = ?,
                per_user_limit = ?,
                audience = ?,
                status = 1
            WHERE promotion_id = ?
              AND status = -1
        ";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $discountPercentCompat = $data['discount_type'] === 'percent'
            ? (float)$data['discount_value']
            : 0;

        $stmt->bind_param(
            "ssddssdiisi",
            $data['name'],
            $data['discount_type'],
            $data['discount_value'],
            $discountPercentCompat,
            $data['start_date'],
            $data['end_date'],
            $data['min_amount'],
            $data['usage_limit'],
            $data['per_user_limit'],
            $data['audience'],
            $promotionId
        );

        return $stmt->execute();
    }

    public function deletePromotion(int $promotionId)
    {
        $sql = "UPDATE promotions SET status = -1 WHERE promotion_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("i", $promotionId);
        return $stmt->execute();
    }

    /**
     * Kết thúc sớm: set end_date = hôm nay - 1 ngày để status thành expired
     */
    public function forceExpire(int $promotionId): bool
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $sql = "UPDATE promotions SET end_date = ?, status = 0 WHERE promotion_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("si", $yesterday, $promotionId);
        return $stmt->execute();
    }

    public function syncStatuses(): void
    {
        // Active: đã đến start_date và chưa quá end_date
        $this->conn->query("
            UPDATE promotions
            SET status = 1
            WHERE status != -1
              AND start_date <= CURDATE()
              AND end_date >= CURDATE()
        ");

        // Expired: đã quá end_date
        $this->conn->query("
            UPDATE promotions
            SET status = 0
            WHERE status != -1
              AND end_date < CURDATE()
        ");

        // Scheduled: chưa đến start_date
        $this->conn->query("
            UPDATE promotions
            SET status = 2
            WHERE status != -1
              AND start_date > CURDATE()
        ");
    }
}
