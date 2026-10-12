<?php
if (!isset($_SESSION['user'])) {
    header("Location: index.php?page=login");
    exit;
}

$userId = $_SESSION['user']['user_id'];

$bills = $userController->getBillsByUserId($userId);

function getStatusLabel($status)
{
    return [
        'pending' => 'Chờ thanh toán',
        'paid' => 'Đã thanh toán',
        'cancelled' => 'Đã hủy'
    ][$status] ?? $status;
}

function getStatusClass($status)
{
    return [
        'pending' => 'status-pending',
        'paid' => 'status-paid',
        'cancelled' => 'status-cancelled'
    ][$status] ?? '';
}
?>

<section class="my-bookings">
    <div class="container">

        <h1 class="page-title">🎟 Lịch sử mua vé</h1>

        <?php if (empty($bills)): ?>
            <div style="text-align:center;padding:60px;color:#888;">
                Bạn chưa có đơn hàng nào.
            </div>
        <?php else: ?>

            <div class="orders-list">
                <?php foreach ($bills as $bill): ?>

                    <div class="order-card status-<?= $bill['status'] ?>">

                        <!-- HEADER -->
                        <div class="order-header">
                            <div class="order-left">
                                <div class="order-id">#BK<?= str_pad($bill['bill_id'], 4, '0', STR_PAD_LEFT) ?></div>
                                <div class="order-date">
                                    <?= date('d/m/Y H:i', strtotime($bill['created_at'])) ?>
                                </div>
                            </div>

                            <div class="order-status status-<?= $bill['status'] ?>">
                                <?= getStatusLabel($bill['status']) ?>
                            </div>
                        </div>

                        <!-- BODY -->
                        <div class="order-body">

                            <div class="order-info">
                                <div class="info-item">
                                    🎟 <?= $bill['total_tickets'] ?> vé
                                </div>

                                <div class="info-item">
                                    💳 Thanh toán
                                </div>
                            </div>

                            <div class="order-total">
                                <?= number_format($bill['final_amount'], 0, ',', '.') ?> ₫
                            </div>

                        </div>

                        <!-- FOOTER -->
                        <div class="order-footer">
                            <a href="index.php?page=booking_success&bill_id=<?= $bill['bill_id'] ?>"
                                class="btn-detail">
                                Xem chi tiết →
                            </a>
                        </div>

                    </div>

                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </div>
</section>