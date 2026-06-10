<!DOCTYPE html>
<html lang="vi">
<?php
$bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
$seatTotal = isset($_POST['seat_total']) ? (float)$_POST['seat_total'] : 0;
$foodTotal = isset($_POST['food_total']) ? (float)$_POST['food_total'] : 0;
$grandTotal = isset($_POST['grand_total']) ? (float)$_POST['grand_total'] : ($seatTotal + $foodTotal);
$showtimeId = isset($_POST['showtime_id']) ? (int)$_POST['showtime_id'] : 0;
$hallId = isset($_POST['hall_id']) ? (int)$_POST['hall_id'] : 0;

$showData = $showController->getShowById($showtimeId);
$seatNames = [];

$ticketIds = isset($_POST['ticket_ids']) && is_array($_POST['ticket_ids'])
    ? array_values(array_filter(array_map('intval', $_POST['ticket_ids'])))
    : [];

if (!empty($_POST['seat_names'])) {
    $decodedSeatNames = json_decode((string)$_POST['seat_names'], true);
    if (is_array($decodedSeatNames)) {
        $seatNames = $decodedSeatNames;
    }
}
$selectedCombos = [];

if (!empty($_POST['combos'])) {
    $decodedCombos = json_decode($_POST['combos'], true);

    if (is_array($decodedCombos)) {
        $selectedCombos = $decodedCombos;
    }
}

$availablePromotions = [];
if (isset($promotionService)) {
    foreach ($promotionService->listPromotions() as $promotion) {
        if (($promotion['computed_status'] ?? '') !== 'active') {
            continue;
        }

        $usageLimit = (int)($promotion['usage_limit'] ?? 0);
        $usageCount = (int)($promotion['usage_count'] ?? 0);
        if ($usageLimit > 0 && $usageCount >= $usageLimit) {
            continue;
        }

        $availablePromotions[] = [
            'code' => (string)($promotion['code'] ?? ''),
            'name' => (string)($promotion['name'] ?? ''),
            'discount_type' => (string)($promotion['discount_type'] ?? 'percent'),
            'discount_value' => (float)($promotion['discount_value'] ?? 0),
            'min_amount' => (float)($promotion['min_amount'] ?? 0),
        ];
    }
}
?>

<head>
    <meta charset="UTF-8">
    <title>Thanh toán</title>
    <link rel="stylesheet" href="/web_cinemax/public/assets/css/style.css">
    <link rel="stylesheet" href="/web_cinemax/public/assets/css/seat-selection.css">
    <link rel="stylesheet" href="/web_cinemax/public/assets/css/food-selection.css">
    <link rel="stylesheet" href="/web_cinemax/public/assets/css/payment.css?v=20260611-2">
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-content">
                <div class="logo"><span>Cinema </span></div>

                <div class="booking-steps">
                    <div class="step completed"><span class="step-number">1</span><span class="step-label">Suất chiếu</span></div>
                    <div class="step completed"><span class="step-number">2</span><span class="step-label">Ghế</span></div>
                    <div class="step completed"><span class="step-number">3</span><span class="step-label">Đồ ăn</span></div>
                    <div class="step active"><span class="step-number">4</span><span class="step-label">Thanh toán</span></div>
                </div>

                <a href="#"
                    class="btn-back" onclick="return confirm('Hủy đơn hàng này?');">Hủy đơn</a>
            </div>
        </div>
    </nav>

    <div class="booking-container">
        <div class="container">
            <div class="booking-layout">

                <div class="payment-section">
                    <h2>Chọn phương thức thanh toán</h2>

                    <form id="paymentForm" action="index.php?page=payment&action=confirm_payment" method="POST">
                        <input type="hidden" name="action" value="confirm_payment">
                        <input type="hidden" name="booking_id" value="<?php echo $bookingId; ?>">
                        <?php foreach ($ticketIds as $tid): ?>
                            <input type="hidden" name="ticket_ids[]" value="<?= $tid ?>">
                        <?php endforeach; ?>
                        <input type="hidden" name="showtime_id" value="<?= $showtimeId ?>">
                        <input type="hidden" name="grand_total" value="<?= $grandTotal ?>">
                        <input type="hidden" name="promotion_code" id="promotionCodeInput" value="">
                        <input type="hidden" name="seat_names" value="<?= htmlspecialchars(json_encode($seatNames, JSON_UNESCAPED_UNICODE)) ?>">
                        <?php if (!empty($selectedCombos)): ?>
                            <?php foreach ($selectedCombos as $index => $combo): ?>
                                <input type="hidden" name="combos[<?= $index ?>][combo_id]" value="<?= (int)$combo['combo_id'] ?>">
                                <input type="hidden" name="combos[<?= $index ?>][quantity]" value="<?= (int)$combo['quantity'] ?>">
                                <input type="hidden" name="combos[<?= $index ?>][price]" value="<?= (float)$combo['price'] ?>">
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <div class="method-list">
                            <label class="method-item active">
                                <input type="radio" name="payment_method" value="ATM" checked>
                                <img src="https://cdn-icons-png.flaticon.com/512/2534/2534204.png" class="method-icon">
                                <div class="method-info">
                                    <h4>Thẻ ATM nội địa / Internet Banking</h4>
                                    <p>Hỗ trợ tất cả các ngân hàng tại Việt Nam</p>
                                </div>
                                <div class="radio-custom"></div>
                            </label>

                            <label class="method-item">
                                <input type="radio" name="payment_method" value="MOMO">
                                <img src="https://upload.wikimedia.org/wikipedia/vi/f/fe/MoMo_Logo.png" class="method-icon">
                                <div class="method-info">
                                    <h4>Ví điện tử MoMo</h4>
                                    <p>Quét mã QR để thanh toán nhanh chóng</p>
                                </div>
                                <div class="radio-custom"></div>
                            </label>

                            <label class="method-item">
                                <input type="radio" name="payment_method" value="VISA">
                                <img src="https://cdn-icons-png.flaticon.com/512/349/349221.png" class="method-icon">
                                <div class="method-info">
                                    <h4>Thẻ Quốc tế (Visa / Master / JCB)</h4>
                                    <p>Thanh toán an toàn, bảo mật</p>
                                </div>
                                <div class="radio-custom"></div>
                            </label>
                        </div>

                        <div class="note-box">
                            ⚠️ <strong>Lưu ý:</strong> Sau khi bấm "Thanh toán", đơn hàng sẽ được chuyển sang trạng thái <strong>Chờ xác nhận</strong>. Vui lòng đợi nhân viên/hệ thống xác nhận trong giây lát.
                        </div>
                    </form>
                </div>

                <div class="booking-sidebar">
                    <div class="booking-summary">
                        <h3 style="border-bottom: 1px solid #444; padding-bottom: 15px; margin-bottom: 15px;">Thông tin đặt vé</h3>

                        <div class="summary-block">
                            <h4 style="color: #fff; margin-bottom: 10px; font-size: 18px;"></h4>
                            <div class="info-row"><span>Rạp:</span> <strong><?php echo htmlspecialchars((string)($showData['cinema_name'] ?? 'Đang cập nhật')); ?></strong></div>
                            <div class="info-row"><span>Suất:</span> <strong><?php echo htmlspecialchars($showData['show_date'] . ' ' . substr($showData['start_time'], 0, 5)); ?></strong></div>
                            <div class="info-row"><span>Phòng:</span> <strong>
                                    <?php echo htmlspecialchars((string)($showData['hall_name'] ?? 'Đang cập nhật')); ?>
                                </strong>
                            </div>
                            <div class="info-row"><span>Ghế:</span> <strong style="color: var(--primary-color);"><?php echo htmlspecialchars(!empty($seatNames) ? implode(', ', $seatNames) : 'Chưa chọn'); ?></strong></div>
                        </div>

                        <div style="margin-top: 20px;">
                            <div class="info-row">
                                <span>Tổng vé:</span>
                                <span><?php echo number_format($seatTotal, 0, ',', '.'); ?> ₫</span>
                            </div>
                            <div class="info-row">
                                <span>Tổng đồ ăn:</span>
                                <span><?php echo number_format($foodTotal, 0, ',', '.'); ?> ₫</span>
                            </div>

                            <div class="promotion-box">
                                <label for="promotionCode">Mã giảm giá</label>
                                <div class="promotion-input-row">
                                    <input type="text" id="promotionCode" maxlength="50"
                                        placeholder="Nhập mã ưu đãi">
                                    <button type="button" id="applyPromotionBtn">Áp dụng</button>
                                </div>
                                <p id="promotionMessage" class="promotion-message"></p>
                            </div>

                            <div class="info-row discount-row" id="discountRow" hidden>
                                <span>Giảm giá:</span>
                                <strong id="discountAmount">-0 ₫</strong>
                            </div>

                            <div style="border-top: 1px dashed #555; margin: 15px 0;"></div>

                            <div class="info-row" style="align-items: center;">
                                <span style="font-size: 16px; font-weight: bold; color: #fff;">Tổng thanh toán:</span>
                                <span class="final-total" id="finalTotal"><?php echo number_format($grandTotal, 0, ',', '.'); ?> ₫</span>
                            </div>
                        </div>

                        <button type="button" onclick="submitPayment()" class="btn-continue">
                            THANH TOÁN NGAY
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        const originalTotal = <?= json_encode((float)$grandTotal) ?>;
        const availablePromotions = <?= json_encode($availablePromotions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

        function formatCurrency(amount) {
            return `${Math.round(amount).toLocaleString('vi-VN')} ₫`;
        }

        function applyPromotion() {
            const codeField = document.getElementById('promotionCode');
            const hiddenCode = document.getElementById('promotionCodeInput');
            const message = document.getElementById('promotionMessage');
            const discountRow = document.getElementById('discountRow');
            const discountAmountEl = document.getElementById('discountAmount');
            const finalTotalEl = document.getElementById('finalTotal');
            const code = codeField.value.trim().toUpperCase();

            codeField.value = code;
            hiddenCode.value = '';
            discountRow.hidden = true;
            finalTotalEl.textContent = formatCurrency(originalTotal);
            message.className = 'promotion-message error';

            if (!code) {
                message.textContent = 'Vui lòng nhập mã giảm giá.';
                return;
            }

            const promotion = availablePromotions.find(item => item.code.toUpperCase() === code);
            if (!promotion) {
                message.textContent = 'Mã giảm giá không hợp lệ hoặc đã hết hạn.';
                return;
            }

            if (originalTotal < Number(promotion.min_amount)) {
                message.textContent = `Đơn tối thiểu ${formatCurrency(promotion.min_amount)} để dùng mã này.`;
                return;
            }

            let discountAmount = promotion.discount_type === 'fixed'
                ? Number(promotion.discount_value)
                : originalTotal * Number(promotion.discount_value) / 100;
            discountAmount = Math.min(originalTotal, Math.max(0, discountAmount));

            hiddenCode.value = promotion.code;
            discountAmountEl.textContent = `-${formatCurrency(discountAmount)}`;
            finalTotalEl.textContent = formatCurrency(originalTotal - discountAmount);
            discountRow.hidden = false;
            message.className = 'promotion-message success';
            message.textContent = `Đã áp dụng: ${promotion.name}`;
        }

        document.getElementById('applyPromotionBtn').addEventListener('click', applyPromotion);
        document.getElementById('promotionCode').addEventListener('keydown', event => {
            if (event.key === 'Enter') {
                event.preventDefault();
                applyPromotion();
            }
        });

        function submitPayment() {
            if (confirm('Xác nhận thanh toán đơn hàng này?')) {
                document.getElementById('paymentForm').submit();
            }
        }

        // Hiệu ứng JS đổi class active khi click
        const methods = document.querySelectorAll('.method-item');
        methods.forEach(m => {
            m.addEventListener('click', () => {
                // Bỏ active ở tất cả
                methods.forEach(x => x.classList.remove('active'));
                // Thêm active vào cái được click
                m.classList.add('active');
                // Check radio button bên trong
                m.querySelector('input').checked = true;
            });
        });
    </script>
</body>

</html>
