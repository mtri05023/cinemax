<?php
if (!isset($promotionService)) {
    die('PromotionService chưa được khởi tạo.');
}

$promotions = $promotionService->listForUser((int)($authUser['user_id']??0));

// Chỉ hiện khuyến mãi còn hiệu lực hoặc sắp diễn ra
$promotions = array_values(array_filter($promotions, function ($promo) use ($authUser) {
    if (!empty($authUser['user_id'])) return $promo['eligible'];
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

<link rel="stylesheet" href="/cinemax/cinemax/public/assets/css/promotions.css?v=<?= filemtime(__DIR__.'/../../../public/assets/css/promotions.css') ?>">
<main class="section promotions-page">
    <div class="container">
        <div class="section-header">
            <h2>Khuyến mãi &amp; Sự kiện</h2>
            <p>Chọn ưu đãi dành cho bạn, tận hưởng những buổi xem phim trọn vẹn hơn.</p>
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
                            <span class="promo-audience"><?= ($promo['audience']??'all')==='new_member'?'Chào thành viên mới · 1 lần/tài khoản':'Dành cho thành viên Cinemax' ?></span>

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
                                <meter class="promo-quota" min="0" max="<?= (int)$promo['usage_limit'] ?>" value="<?= max(0,(int)$promo['usage_limit']-(int)$promo['usage_count']) ?>" aria-label="Số lượng ưu đãi còn lại"></meter>
                            <?php endif; ?>

                            <?php if (!$promo['eligible']): ?>
                                <p class="promo-condition promo-unavailable"><?= htmlspecialchars($promo['computed_status']==='scheduled'?'Áp dụng từ '.formatPromotionDateVN($promo['start_date']):$promo['eligibility_reason']) ?></p>
                            <?php endif; ?>
                            <button type="button" class="promo-copy" data-code="<?= htmlspecialchars($promo['code'],ENT_QUOTES,'UTF-8') ?>" <?= $promo['eligible']?'':'disabled' ?>><?= $promo['eligible']?'Sao chép mã':'Chưa thể sử dụng' ?></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>
<script>
document.querySelectorAll('.promo-copy:not(:disabled)').forEach(button => button.addEventListener('click', async () => {
    try { await navigator.clipboard.writeText(button.dataset.code); button.textContent='Đã sao chép'; }
    catch (error) { button.textContent='Mã: '+button.dataset.code; }
}));
</script>
