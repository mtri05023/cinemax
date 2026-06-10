<?php
$billId = isset($_GET['bill_id']) ? (int)$_GET['bill_id'] : 0;

$billData = null;
$tickets  = [];
$combos   = [];

if ($billId > 0) {
    $sqlBill = "SELECT b.*, u.full_name, u.email
                FROM bills b
                JOIN users u ON u.user_id = b.user_id
                WHERE b.bill_id = ?";
    $stmtBill = $conn->prepare($sqlBill);
    $stmtBill->bind_param('i', $billId);
    $stmtBill->execute();
    $billData = $stmtBill->get_result()->fetch_assoc();

    if ($billData) {
        $tickets = $ticketService->getTicketsByBillId($billId);

        $sqlCombos = "SELECT bc.quantity, bc.price, co.name
                      FROM bill_combos bc
                      JOIN combos co ON co.combo_id = bc.combo_id
                      WHERE bc.bill_id = ?";
        $stmtCombos = $conn->prepare($sqlCombos);
        $stmtCombos->bind_param('i', $billId);
        $stmtCombos->execute();
        $resultCombos = $stmtCombos->get_result();
        while ($row = $resultCombos->fetch_assoc()) $combos[] = $row;
    }
}



$isPaid      = $billData && $billData['status'] === 'paid';
$isPending   = $billData && $billData['status'] === 'pending';
$isCancelled = $billData && $billData['status'] === 'cancelled';
?>

<section class="booking_success">
    <main class="section">
        <div class="container result-container">

            <?php if (!$billData): ?>
                <div style="text-align:center;padding:60px;color:#e50914;">
                    <h1>Không tìm thấy đơn hàng.</h1>
                    <a href="index.php" class="btn-action btn-home">Về trang chủ</a>
                </div>

            <?php elseif ($isCancelled): ?>
                <div style="text-align:center;color:#e50914;">
                    <h1>Đơn hàng đã bị hủy</h1>
                    <p>Vui lòng đặt lại vé mới.</p>
                    <a href="index.php" class="btn-action btn-home" style="margin-top:20px;display:inline-block;">Quay về trang chủ</a>
                </div>

            <?php elseif ($isPending): ?>
                <!-- GIỮ NGUYÊN -->
                <div class="pending-card">
                    <div class="pending-icon">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <h1>Đơn hàng đang chờ xác nhận</h1>
                    <p>Cảm ơn bạn đã đặt vé. Hệ thống đang xử lý giao dịch của bạn.<br>
                        Vui lòng đợi nhân viên xác nhận thanh toán để nhận vé điện tử.</p>
                    <div class="order-ref">Mã đơn: #<?php echo $billId; ?></div>
                    <p style="font-size:14px;color:#888;">
                        ⚠️ Vé chưa có hiệu lực cho đến khi trạng thái là "Thanh toán thành công".
                    </p>
                    <div class="btn-group">
                        <a href="index.php" class="btn-action btn-home">Về trang chủ</a>
                        <a href="index.php?page=booking_success&bill_id=<?php echo $billId; ?>" class="btn-action btn-reload">🔄 Kiểm tra lại</a>
                    </div>
                </div>

            <?php elseif ($isPaid): ?>

                <div class="success-section">

                    <!-- HEADER -->
                    <div class="success-header">
                        <div class="success-icon">
                            ✔
                        </div>
                        <h1 class="success-title">Đặt vé thành công!</h1>

                        <div class="success-meta">
                            <span>Mã đơn: <strong>#<?php echo $billId; ?></strong></span>
                            <span class="meta-dot"></span>
                            <span>
                                Tổng thanh toán:
                                <strong class="price">
                                    <?php echo number_format($billData['final_amount'], 0, ',', '.'); ?> ₫
                                </strong>
                            </span>
                        </div>
                    </div>

                    <!-- CAROUSEL -->
                    <div class="tickets-carousel">

                        <?php if (count($tickets) > 1): ?>
                            <button class="carousel-btn prev" onclick="prevTicket()">‹</button>
                            <button class="carousel-btn next" onclick="nextTicket()">›</button>
                        <?php endif; ?>

                        <div class="carousel-container">
                            <div class="carousel-track" id="carousel-track">

                                <?php foreach ($tickets as $index => $ticket):
                                    $seatLabel = $ticket['row_name'] . $ticket['seat_number'];
                                    $qrData = "CINEMAX-BILL{$billId}-TICKET{$ticket['ticket_id']}-SEAT{$seatLabel}";
                                    $qrUrl  = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($qrData);
                                ?>

                                    <div class="carousel-slide">
                                        <div class="ticket-card">

                                            <!-- HEADER -->
                                            <div class="ticket-header">
                                                <div class="ticket-header-content">
                                                    <div class="ticket-label">
                                                        🎬 <span>Vé xem phim</span>
                                                    </div>
                                                    <span class="ticket-id">#<?php echo $ticket['ticket_id']; ?></span>
                                                </div>
                                            </div>

                                            <!-- TITLE -->
                                            <div class="ticket-title-section">
                                                <h3 class="ticket-movie-title">
                                                    <?php echo $ticket['movie_title']; ?>
                                                </h3>
                                            </div>

                                            <!-- DETAILS -->
                                            <div class="ticket-details">
                                                <div class="ticket-grid">

                                                    <div class="ticket-info">
                                                        <div class="ticket-info-label">Rạp</div>
                                                        <p class="ticket-info-value"><?php echo $ticket['cinema_name']; ?></p>
                                                    </div>

                                                    <div class="ticket-info">
                                                        <div class="ticket-info-label">Phòng</div>
                                                        <p class="ticket-info-value"><?php echo $ticket['hall_name']; ?></p>
                                                    </div>

                                                    <div class="ticket-info">
                                                        <div class="ticket-info-label">Suất chiếu</div>
                                                        <p class="ticket-info-value">
                                                            <?php echo date('d/m/Y', strtotime($ticket['show_date'])) . ' ' . substr($ticket['start_time'], 0, 5); ?>
                                                        </p>
                                                    </div>

                                                    <div class="ticket-info">
                                                        <div class="ticket-info-label">Ghế</div>
                                                        <p class="ticket-info-value">
                                                            <?php echo $seatLabel; ?>
                                                        </p>
                                                    </div>

                                                </div>

                                                <!-- PRICE -->
                                                <div class="ticket-price-row">
                                                    <span class="ticket-price-label">Giá vé</span>
                                                    <span class="ticket-price-value">
                                                        <?php echo number_format($ticket['price'], 0, ',', '.'); ?> ₫
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- TEAR -->
                                            <div class="ticket-tear">
                                                <div class="ticket-tear-line"></div>
                                            </div>

                                            <!-- QR -->
                                            <div class="ticket-qr-section">
                                                <div class="qr-wrapper">
                                                    <img src="<?php echo $qrUrl; ?>">
                                                </div>
                                                <p class="qr-hint">Đưa mã này cho nhân viên để vào rạp</p>
                                            </div>

                                        </div>
                                    </div>

                                <?php endforeach; ?>

                            </div>
                        </div>

                        <!-- DOTS -->
                        <?php if (count($tickets) > 1): ?>
                            <div class="carousel-dots">
                                <?php foreach ($tickets as $i => $t): ?>
                                    <button class="carousel-dot <?php echo $i === 0 ? 'active' : ''; ?>"
                                        onclick="goToTicket(<?php echo $i; ?>)">
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    </div>

                    <?php if (!empty($combos)): ?>
    <div class="combo-section">
        <h3 class="combo-title">🍿 Combo bắp nước</h3>

        <div class="combo-list">
            <?php 
            $comboTotal = 0;
            foreach ($combos as $c): 
                $itemTotal = $c['price'] * $c['quantity'];
                $comboTotal += $itemTotal;
            ?>
                <div class="combo-item">
                    <div class="combo-left">
                        <span class="combo-name">
                            <?= htmlspecialchars($c['name']) ?>
                        </span>
                        <span class="combo-qty">
                            x<?= $c['quantity'] ?>
                        </span>
                    </div>
                    <div class="combo-price">
                        <?= number_format($itemTotal, 0, ',', '.') ?> ₫
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="combo-total">
                <span>Tổng đồ ăn</span>
                <strong><?= number_format($comboTotal, 0, ',', '.') ?> ₫</strong>
            </div>
        </div>
    </div>
<?php endif; ?>

                    <!-- BUTTON -->
                    <div class="action-buttons">
                        <a href="index.php" class="btn btn-outline">
                            🏠 Về trang chủ
                        </a>
                    </div>

                </div>

            <?php endif; ?>

        </div>
    </main>
</section>