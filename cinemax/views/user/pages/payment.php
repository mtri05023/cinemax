<!DOCTYPE html>
<html lang="vi">
<?php
$bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
$seatTotal = isset($_POST['seat_total']) ? (float)$_POST['seat_total'] : 0;
$foodTotal = isset($_POST['food_total']) ? (float)$_POST['food_total'] : 0;
$grandTotal = isset($_POST['grand_total']) ? (float)$_POST['grand_total'] : ($seatTotal + $foodTotal);
$showtimeId = (int)$checkoutHold['show_id'];
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
    $decodedCombos = is_array($_POST['combos']) ? $_POST['combos'] : json_decode($_POST['combos'], true);

    if (is_array($decodedCombos)) {
        $selectedCombos = $decodedCombos;
    }
}

// Server data replaces all prices, names, and ticket IDs supplied by the browser.
$ticketIds=array_column($checkoutHold['tickets'],'ticket_id');
$seatNames=array_column($checkoutHold['tickets'],'seat_name');
$showtimeId=(int)$checkoutHold['show_id'];
$showData=$showController->getShowById($showtimeId);
$seatTotal=(float)$checkoutHold['seat_total'];
$foodTotal=(float)$checkoutHold['food']['total'];
$grandTotal=$seatTotal+$foodTotal;
$selectedCombos=$checkoutHold['food']['combos'];
$availablePromotions = [];
foreach ($promotionService->listForUser((int)$authUser['user_id'], $grandTotal) as $promotion) {
    if (!$promotion['eligible']) continue;
    $availablePromotions[] = [
        'code'=>(string)$promotion['code'], 'name'=>(string)$promotion['name'],
        'discount_type'=>(string)$promotion['discount_type'],
        'discount_value'=>(float)$promotion['discount_value'],
        'min_amount'=>(float)$promotion['min_amount'],
    ];
}
?>

<head>
    <meta charset="UTF-8">
    <title>Thanh toán</title>
    <link rel="stylesheet" href="/cinemax/cinemax/public/assets/css/style.css">
    <link rel="stylesheet" href="/cinemax/cinemax/public/assets/css/seat-selection.css">
    <link rel="stylesheet" href="/cinemax/cinemax/public/assets/css/food-selection.css">
    <link rel="stylesheet" href="/cinemax/cinemax/public/assets/css/payment.css?v=<?= filemtime(__DIR__.'/../../../public/assets/css/payment.css') ?>">
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

                <form action="index.php?page=payment&action=cancel_checkout" method="POST" onsubmit="return confirm('Hủy thanh toán và trả lại các ghế đang giữ?');">
                    <input type="hidden" name="hold_token" value="<?= htmlspecialchars($checkoutHold['token']) ?>">
                    <input type="hidden" name="checkout_csrf" value="<?= htmlspecialchars($_SESSION['checkout_csrf']) ?>">
                    <button type="submit" class="btn-back">Hủy thanh toán</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="booking-container payment-page">
        <div class="container">
            <div class="booking-layout">

                <div class="payment-section">
                    <h2>Chọn phương thức thanh toán</h2>

                    <form id="paymentForm" action="index.php?page=payment&action=confirm_payment" method="POST">
                        <input type="hidden" name="hold_token" value="<?= htmlspecialchars($checkoutHold['token']) ?>">
                        <input type="hidden" name="checkout_csrf" value="<?= htmlspecialchars($_SESSION['checkout_csrf']) ?>">
                        <?php if (!empty($_SESSION['checkout_error'])): ?>
                            <p class="promotion-message error"><?= htmlspecialchars($_SESSION['checkout_error']) ?></p>
                            <?php unset($_SESSION['checkout_error']); ?>
                        <?php endif; ?>
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
                                <span class="method-icon momo-mark" aria-label="MoMo">MoMo</span>
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
                                <div class="voucher-heading"><div><span class="voucher-eyebrow">ƯU ĐÃI CINEMAX</span><h4>Chọn mã giảm giá</h4></div><span class="voucher-count"><?= count($availablePromotions) ?> mã</span></div>
                                <p class="voucher-description">Chọn một ưu đãi để giảm ngay tiền vé.</p>
                                <select id="promotionSelect" hidden aria-label="Mã giảm giá đã chọn" <?= $availablePromotions ? '' : 'disabled' ?>>
                                    <option value=""><?= $availablePromotions ? 'Chọn mã giảm giá' : 'Chưa có mã phù hợp với đơn hàng này' ?></option>
                                    <?php foreach ($availablePromotions as $promotion): ?>
                                        <option value="<?= htmlspecialchars($promotion['code']) ?>"><?= htmlspecialchars($promotion['code'].' — '.$promotion['name'].' (Giảm '.($promotion['discount_type']==='percent' ? $promotion['discount_value'].'%' : number_format($promotion['discount_value'],0,',','.').' ₫').')') ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="voucher-list" tabindex="0" role="group" aria-label="Danh sách mã giảm giá phù hợp, có thể cuộn để xem thêm">
                                    <?php foreach ($availablePromotions as $promotion): ?>
                                        <button type="button" class="voucher-card" data-code="<?= htmlspecialchars($promotion['code']) ?>" aria-pressed="false">
                                            <span class="voucher-value"><small>GIẢM</small><strong><?= htmlspecialchars($promotion['discount_type']==='percent' ? $promotion['discount_value'].'%' : number_format($promotion['discount_value'],0,',','.')) ?></strong><?php if ($promotion['discount_type']!=='percent'): ?><small>ĐỒNG</small><?php endif; ?></span>
                                            <span class="voucher-content"><strong class="voucher-name"><?= htmlspecialchars($promotion['name']) ?></strong><span class="voucher-code"><?= htmlspecialchars($promotion['code']) ?></span><span class="voucher-conditions"><?= $promotion['min_amount']>0 ? 'Đơn từ '.number_format($promotion['min_amount'],0,',','.').' ₫' : 'Áp dụng cho đơn hàng của bạn' ?></span><span class="voucher-action">Chọn mã <span aria-hidden="true">→</span></span></span>
                                        </button>
                                    <?php endforeach; ?>
                                    <?php if (!$availablePromotions): ?><p class="voucher-empty">Chưa có ưu đãi phù hợp với đơn hàng này.</p><?php endif; ?>
                                </div>
                                <?php if (count($availablePromotions)>2): ?><p class="voucher-scroll-hint">Cuộn để xem thêm ưu đãi ↓</p><?php endif; ?>
                                <button type="button" id="clearPromotionBtn" class="voucher-clear" hidden>Bỏ chọn mã giảm giá</button>
                                <label for="promotionCode" class="voucher-manual-label">Hoặc nhập mã ưu đãi</label>
                                <div class="promotion-input-row">
                                    <input type="text" id="promotionCode" maxlength="50"
                                        placeholder="Nhập mã ưu đãi">
                                    <button type="button" id="applyPromotionBtn">Áp dụng</button>
                                </div>
                                <p id="promotionMessage" class="promotion-message" role="status" aria-live="polite"></p>
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
        const availablePromotions = <?= json_encode($availablePromotions, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        function formatCurrency(amount) {
            return `${Math.round(amount).toLocaleString('vi-VN')} ₫`;
        }

        function syncVoucherCards() {
            const code = document.getElementById('promotionCodeInput').value;
            document.querySelectorAll('.voucher-card').forEach(card => {
                const selected = card.dataset.code === code;
                card.classList.toggle('is-selected', selected);
                card.setAttribute('aria-pressed', String(selected));
                card.querySelector('.voucher-action').textContent = selected ? '✓ Đã chọn' : 'Chọn mã →';
            });
            document.getElementById('clearPromotionBtn').hidden = !code;
        }
        document.querySelectorAll('.voucher-card').forEach(card => card.addEventListener('click', () => {
            const selected = card.getAttribute('aria-pressed') === 'true';
            document.getElementById('promotionCode').value = selected ? '' : card.dataset.code;
            if (selected) clearAppliedPromotion();
            else applyPromotion();
        }));
        document.getElementById('clearPromotionBtn').addEventListener('click', () => {
            document.getElementById('promotionCode').value = '';
            clearAppliedPromotion();
        });

        function clearAppliedPromotion() {
            document.getElementById('promotionCodeInput').value = '';
            document.getElementById('promotionSelect').value = '';
            document.getElementById('discountRow').hidden = true;
            document.getElementById('finalTotal').textContent = formatCurrency(originalTotal);
            document.getElementById('promotionMessage').textContent = '';
            syncVoucherCards();
        }
        document.getElementById('promotionSelect').addEventListener('change', event => {
            document.getElementById('promotionCode').value = event.target.value;
            if (event.target.value) applyPromotion();
            else clearAppliedPromotion();
        });
        document.getElementById('promotionCode').addEventListener('input', clearAppliedPromotion);

        function applyPromotion() {
            clearAppliedPromotion();
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
                message.textContent = 'Mã giảm giá không đủ điều kiện cho tài khoản hoặc đơn hàng này, hoặc đã hết hạn.';
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
            document.getElementById('promotionSelect').value = promotion.code;
            discountAmountEl.textContent = `-${formatCurrency(discountAmount)}`;
            finalTotalEl.textContent = formatCurrency(originalTotal - discountAmount);
            discountRow.hidden = false;
            message.className = 'promotion-message success';
            message.textContent = `Đã áp dụng: ${promotion.name}`;
            syncVoucherCards();
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
                document.getElementById('paymentForm').requestSubmit();
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
<?php require __DIR__.'/../partials/checkout_timer.php'; ?>
</body>

</html>
