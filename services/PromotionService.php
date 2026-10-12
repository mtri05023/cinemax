<?php

class PromotionService
{
    private $promotionModel;

    public function __construct($promotionModel)
    {
        $this->promotionModel = $promotionModel;
    }

    public function listPromotions($search = '')
    {
        $this->promotionModel->syncStatuses();
        $rows = $this->promotionModel->getAllPromotions($search);

        foreach ($rows as &$row) {
            $row['computed_status'] = $this->computeStatus(
                $row['start_date'] ?? '',
                $row['end_date'] ?? ''
            );
        }

        return $rows;
    }

    public function getPromotionDetail($promotionId)
    {
        $this->promotionModel->syncStatuses();

        if (!is_numeric($promotionId) || (int)$promotionId <= 0) {
            throw new InvalidArgumentException("ID khuyến mãi không hợp lệ.");
        }

        $promotion = $this->promotionModel->getPromotionById((int)$promotionId);
        if (!$promotion) {
            throw new RuntimeException("Không tìm thấy khuyến mãi.");
        }

        $promotion['computed_status'] = $this->computeStatus(
            $promotion['start_date'] ?? '',
            $promotion['end_date'] ?? ''
        );

        return $promotion;
    }

    public function applyPromotionCode(string $code, float $totalAmount): array
    {
        $this->promotionModel->syncStatuses();
        $code = strtoupper(trim($code));

        if ($code === '') {
            throw new InvalidArgumentException("Vui lòng nhập mã giảm giá.");
        }

        if ($totalAmount <= 0) {
            throw new InvalidArgumentException("Tổng tiền thanh toán không hợp lệ.");
        }

        $promotion = $this->promotionModel->getPromotionByCode($code, true);
        if (!$promotion || (int)($promotion['status'] ?? -1) === -1) {
            throw new InvalidArgumentException("Mã giảm giá không tồn tại.");
        }

        if ((int)($promotion['status'] ?? 0) !== 1) {
            throw new InvalidArgumentException("Mã giảm giá hiện không thể sử dụng.");
        }

        $now = new DateTime();
        $startDate = new DateTime((string)$promotion['start_date']);
        $endDate = new DateTime((string)$promotion['end_date']);

        if ($now < $startDate) {
            throw new InvalidArgumentException("Mã giảm giá chưa đến thời gian áp dụng.");
        }

        if ($now > $endDate) {
            throw new InvalidArgumentException("Mã giảm giá đã hết hạn.");
        }

        $minAmount = (float)($promotion['min_amount'] ?? 0);
        if ($totalAmount < $minAmount) {
            throw new InvalidArgumentException(
                "Đơn hàng tối thiểu để dùng mã là " .
                number_format($minAmount, 0, ',', '.') . "đ."
            );
        }

        $usageLimit = (int)($promotion['usage_limit'] ?? 0);
        $usageCount = $this->promotionModel->countPromotionUsage((int)$promotion['promotion_id']);
        if ($usageLimit > 0 && $usageCount >= $usageLimit) {
            throw new InvalidArgumentException("Mã giảm giá đã hết lượt sử dụng.");
        }

        $discountValue = (float)($promotion['discount_value'] ?? 0);
        $discountAmount = ($promotion['discount_type'] ?? 'percent') === 'fixed'
            ? $discountValue
            : $totalAmount * $discountValue / 100;
        $discountAmount = min($totalAmount, max(0, $discountAmount));

        return [
            'promotion_id' => (int)$promotion['promotion_id'],
            'code' => (string)$promotion['code'],
            'name' => (string)$promotion['name'],
            'discount_amount' => round($discountAmount, 2),
            'final_amount' => round($totalAmount - $discountAmount, 2),
        ];
    }

    public function createPromotion(array $data)
    {
        $clean = $this->sanitizePromotionPayload($data, true);

        $existing = $this->promotionModel->getPromotionByCode($clean['code']);
        if ($existing) {
            if ((int)$existing['status'] !== -1) {
                throw new InvalidArgumentException("Mã khuyến mãi đã tồn tại.");
            }

            if (!$this->promotionModel->restorePromotion((int)$existing['promotion_id'], $clean)) {
                throw new RuntimeException("Không thể thêm lại mã khuyến mãi đã xóa.");
            }

            $this->promotionModel->syncStatuses();
            return (int)$existing['promotion_id'];
        }

        $promotionId = $this->promotionModel->createPromotion($clean);
        if (!$promotionId) {
            throw new RuntimeException("Không thể thêm khuyến mãi.");
        }

        $this->promotionModel->syncStatuses();
        return $promotionId;
    }

    public function updatePromotion($promotionId, array $data)
    {
        $this->promotionModel->syncStatuses();

        if (!is_numeric($promotionId) || (int)$promotionId <= 0) {
            throw new InvalidArgumentException("ID khuyến mãi không hợp lệ.");
        }

        $promotionId = (int)$promotionId;
        $existingPromotion = $this->promotionModel->getPromotionById($promotionId);

        if (!$existingPromotion) {
            throw new RuntimeException("Không tìm thấy khuyến mãi cần cập nhật.");
        }

        $clean = $this->sanitizePromotionPayload($data, false);

        $sameCode = $this->promotionModel->getPromotionByCode($clean['code']);
        if (
            $sameCode &&
            (int)$sameCode['promotion_id'] !== $promotionId
        ) {
            throw new InvalidArgumentException("Mã khuyến mãi đã tồn tại.");
        }

        $ok = $this->promotionModel->updatePromotion($promotionId, $clean);
        if (!$ok) {
            throw new RuntimeException("Không thể cập nhật khuyến mãi.");
        }

        $this->promotionModel->syncStatuses();
        return true;
    }

    public function deletePromotion($promotionId)
    {
        if (!is_numeric($promotionId) || (int)$promotionId <= 0) {
            throw new InvalidArgumentException("ID khuyến mãi không hợp lệ.");
        }

        $ok = $this->promotionModel->deletePromotion((int)$promotionId);
        if (!$ok) {
            throw new RuntimeException("Không thể xóa khuyến mãi.");
        }

        return true;
    }

    public function endPromotion($promotionId)
    {
        $this->promotionModel->syncStatuses();

        if (!is_numeric($promotionId) || (int)$promotionId <= 0) {
            throw new InvalidArgumentException("ID khuyến mãi không hợp lệ.");
        }

        $promotionId = (int)$promotionId;
        $promotion = $this->promotionModel->getPromotionById($promotionId);

        if (!$promotion) {
            throw new RuntimeException("Không tìm thấy khuyến mãi.");
        }

        // Đọc thẳng status từ DB (đã được syncStatuses() cập nhật đúng)
        // tránh dùng computeStatus() vì PHP có thể bị lệch timezone
        $dbStatus = (int)($promotion['status'] ?? 0);

        if ($dbStatus !== 1) {
            throw new RuntimeException("Chỉ có thể kết thúc sớm mã đang áp dụng.");
        }

        $ok = $this->promotionModel->forceExpire($promotionId);
        if (!$ok) {
            throw new RuntimeException("Không thể kết thúc sớm khuyến mãi.");
        }

        return true;
    }

    public function computeStatus(string $startDate, string $endDate): string
    {
        $today = date('Y-m-d');

        if ($today < $startDate) {
            return 'scheduled';
        }

        if ($today > $endDate) {
            return 'expired';
        }

        return 'active';
    }

    private function sanitizePromotionPayload(array $data, bool $isCreate = true): array
    {
        $code = strtoupper(trim((string)($data['code'] ?? '')));
        $name = trim((string)($data['name'] ?? ''));
        $discountType = trim((string)($data['discount_type'] ?? 'percent'));
        $discountValue = $data['discount_value'] ?? 0;
        $startDate = trim((string)($data['start_date'] ?? ''));
        $endDate = trim((string)($data['end_date'] ?? ''));
        $minAmount = $data['min_amount'] ?? 0;
        $usageLimit = $data['usage_limit'] ?? 0;

        if ($code === '') {
            throw new InvalidArgumentException("Mã code không được để trống.");
        }

        if (mb_strlen($code) > 50) {
            throw new InvalidArgumentException("Mã code không được vượt quá 50 ký tự.");
        }

        if (!preg_match('/^[A-Z0-9_-]+$/', $code)) {
            throw new InvalidArgumentException("Mã code chỉ được chứa chữ hoa, số, gạch dưới hoặc gạch ngang.");
        }

        if ($name === '') {
            throw new InvalidArgumentException("Tên khuyến mãi không được để trống.");
        }

        if (mb_strlen($name) > 150) {
            throw new InvalidArgumentException("Tên khuyến mãi không được vượt quá 150 ký tự.");
        }

        if (!in_array($discountType, ['percent', 'fixed'], true)) {
            throw new InvalidArgumentException("Loại giảm giá không hợp lệ.");
        }

        if (!is_numeric($discountValue)) {
            throw new InvalidArgumentException("Giá trị giảm không hợp lệ.");
        }

        $discountValue = (float)$discountValue;

        if ($discountValue < 1) {
            throw new InvalidArgumentException("Giá trị giảm phải lớn hơn hoặc bằng 1.");
        }

        if ($discountType === 'percent' && $discountValue > 100) {
            throw new InvalidArgumentException("Giảm theo % không được vượt quá 100.");
        }

        if (!is_numeric($minAmount)) {
            throw new InvalidArgumentException("Đơn tối thiểu không hợp lệ.");
        }

        $minAmount = (float)$minAmount;

        if ($minAmount < 0) {
            throw new InvalidArgumentException("Đơn tối thiểu không được âm.");
        }

        if (!is_numeric($usageLimit) || (int)$usageLimit != (float)$usageLimit) {
            throw new InvalidArgumentException("Giới hạn lượt dùng phải là số nguyên.");
        }

        $usageLimit = (int)$usageLimit;
        if ($usageLimit < 0) {
            throw new InvalidArgumentException("Giới hạn lượt dùng không được âm.");
        }

        $this->validateDate($startDate, "Ngày bắt đầu");
        $this->validateDate($endDate, "Ngày kết thúc");

        $today = date('Y-m-d');

        // Khi thêm mới, chương trình không được bắt đầu trong quá khứ.
        // Khi cập nhật, giữ ngày bắt đầu cũ để mã đang áp dụng vẫn sửa được.
        if ($isCreate && $startDate < $today) {
            throw new InvalidArgumentException("Ngày bắt đầu không được nhỏ hơn ngày hiện tại.");
        }

        // Rule: end_date không nhỏ hơn start_date
        if ($endDate < $startDate) {
            throw new InvalidArgumentException("Ngày kết thúc không được nhỏ hơn ngày bắt đầu.");
        }

        return [
            'code' => $code,
            'name' => $name,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'start_date' => $startDate . ' 00:00:00',
            'end_date' => $endDate . ' 23:59:59',
            'min_amount' => $minAmount,
            'usage_limit' => $usageLimit,
        ];
    }

    private function validateDate(string $date, string $fieldName): void
    {
        if ($date === '') {
            throw new InvalidArgumentException($fieldName . " không được để trống.");
        }

        $d = DateTime::createFromFormat('Y-m-d', $date);
        $errors = DateTime::getLastErrors();

        if (
            !$d ||
            $d->format('Y-m-d') !== $date ||
            ($errors && $errors['warning_count'] > 0) ||
            ($errors && $errors['error_count'] > 0)
        ) {
            throw new InvalidArgumentException($fieldName . " không hợp lệ.");
        }
    }
}
