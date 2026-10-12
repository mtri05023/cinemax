<?php
if (!isset($promotionService)) {
    die('PromotionService chưa được khởi tạo.');
}

$promotions = $promotionService->listPromotions();

// Chỉ hiện khuyến mãi còn hiệu lực hoặc sắp diễn ra
$promotions = array_values(array_filter($promotions, function ($promo) {
    return in_array(($promo['computed_status'] ?? ''), ['active', 'scheduled'], true);
}));

function formatPromotionDateVN($date)
{
    if (!$date) return 'Chưa cập nhật';
    return date('d/m/Y', strtotime($date));
}

function getPromotionBadgeText($promo)
{
    $type = $promo['discount_type'] ?? 'percent';
    $value = (float)($promo['discount_value'] ?? 0);

    if ($type === 'percent') {
        return 'Giảm ' . rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') . '%';
    }

    return 'Giảm ' . number_format($value, 0, ',', '.') . 'đ';
}

function getPromotionStatusText($status)
{
    return match ($status) {
        'active' => 'Đang áp dụng',
        'scheduled' => 'Sắp diễn ra',
        'expired' => 'Đã hết hạn',
        default => 'Không xác định',
    };
}
?>

<main class="section">
    <div class="container">
        <div class="section-header">
            <h2>Khuyến mãi &amp; Sự kiện</h2>
        </div>

        <div class="promo-grid">
            <?php if (empty($promotions)): ?>
                <p>Hiện chưa có khuyến mãi nào đang áp dụng hoặc sắp diễn ra.</p>
            <?php else: ?>
                <?php foreach ($promotions as $promo): ?>
                    <div class="promo-card">
                        <div class="promo-visual" aria-hidden="true">
                            <div class="promo-ticket-icon">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                            <span class="promo-visual-label">Cinemax ưu đãi</span>
                            <strong><?= htmlspecialchars(getPromotionBadgeText($promo)) ?></strong>
                            <span class="promo-visual-code">
                                <?= htmlspecialchars($promo['code'] ?? 'CINEMAX') ?>
                            </span>
                        </div>

                        <div class="promo-content">
                            <span class="promo-badge">
                                <?= htmlspecialchars(getPromotionBadgeText($promo)) ?>
                            </span>

                            <h3><?= htmlspecialchars($promo['name'] ?? 'Ưu đãi Cinemax') ?></h3>

                            <div class="promo-code-row">
                                <span>Mã ưu đãi</span>
                                <strong><?= htmlspecialchars($promo['code'] ?? '') ?></strong>
                            </div>

                            <p class="promo-expiry">
                                <i class="far fa-clock"></i>
                                Hạn dùng: <?= htmlspecialchars(formatPromotionDateVN($promo['end_date'] ?? null)) ?>
                            </p>

                            <?php if ((float)($promo['min_amount'] ?? 0) > 0): ?>
                                <p class="promo-condition">
                                    Áp dụng cho đơn từ
                                    <?= number_format((float)$promo['min_amount'], 0, ',', '.') ?>đ
                                </p>
                            <?php endif; ?>

                            <?php if ((int)($promo['usage_limit'] ?? 0) > 0): ?>
                                <p class="promo-condition">
                                    Còn
                                    <?= max(0, (int)$promo['usage_limit'] - (int)($promo['usage_count'] ?? 0)) ?>
                                    lượt sử dụng
                                </p>
                            <?php endif; ?>

                            <div class="promo-link">
                                Sẵn sàng sử dụng
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>
