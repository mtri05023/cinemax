<?php
$search     = $_GET['q'] ?? '';
$promotions = $promotionController->getAllPromotions($search);
$error      = $_GET['error'] ?? null;
?>

<style>
    .field-error {
        margin-top: 6px;
        color: #ff8f8f;
        font-size: 13px;
        line-height: 1.4;
    }

    .input-error {
        border: 1px solid #ff5f5f !important;
        box-shadow: 0 0 0 1px rgba(255, 95, 95, 0.15);
    }

    .btn-action.disabled {
        pointer-events: none;
        opacity: 0.55;
        cursor: not-allowed;
    }

    .btn-action.warning {
        background: #7a4f10;
        color: #ffe0a0;
        border: 1px solid #c4841e;
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
    }

    .btn-action.warning:hover {
        background: #9a6215;
        color: #fff3cc;
    }

    .action-group {
        display: flex;
        flex-direction: column;
        align-items: center;
        /* căn giữa theo chiều ngang */
        justify-content: center;
        /* căn giữa theo chiều dọc nếu cần */
        gap: 6px;
        width: 100%;
        /* QUAN TRỌNG: chiếm full td */
    }

    .action-group .btn-action {
        width: 110px;
        /* cho đều nhau */
        text-align: center;
    }

    /* Style riêng cho từng loại nút */
    .btn-action {
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid transparent;
    }

    .btn-action:hover {
        opacity: 0.9;
    }

    /* Disabled */
    .btn-action.disabled {
        background: #333;
        color: #aaa;
    }

    /* Warning giữ nguyên nhưng tweak nhẹ */
    .btn-action.warning {
        background: #7a4f10;
        color: #ffe0a0;
        border: 1px solid #c4841e;
    }
</style>

<section class="promotions">

    <header class="admin-header">
        <h1>Quản lý Khuyến mãi</h1>
        <div class="header-actions">
            <?php if (hasPermission('promotions_create')): ?>
                <button class="btn-add" onclick="openModal('addPromoModal')">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Thêm mã mới</span>
                </button>
            <?php endif; ?>
        </div>
    </header>

    <div class="dashboard-content">
        <?php if ($error || isset($_GET['add']) || isset($_GET['update']) || isset($_GET['delete']) || isset($_GET['end'])): ?>
            <div class="alert <?= $error ? 'alert-error' : 'alert-success' ?>" id="autoAlert">
                <?php
                if ($error) {
                    echo htmlspecialchars($error);
                }
                if (isset($_GET['add']) && $_GET['add'] == 1) {
                    echo 'Thêm khuyến mãi thành công!';
                } elseif (isset($_GET['update']) && $_GET['update'] == 1) {
                    echo 'Cập nhật khuyến mãi thành công!';
                } elseif (isset($_GET['end']) && $_GET['end'] == 1) {
                    echo 'Đã kết thúc sớm khuyến mãi thành công!';
                } elseif (isset($_GET['delete']) && $_GET['delete'] == 1) {
                    echo 'Xóa khuyến mãi thành công!';
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-card">

            <form method="GET" action="index.php" class="filter-bar" style="margin-bottom:16px;">
                <input type="hidden" name="page" value="promotions">
                <input
                    type="text"
                    name="q"
                    value="<?= htmlspecialchars($search) ?>"
                    placeholder="Tìm mã hoặc tên khuyến mãi...">
                <button type="submit" class="btn-primary">Tìm</button>
                <a href="index.php?page=promotions" class="btn-action">Reset</a>
            </form>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Mã Code</th>
                            <th>Tên khuyến mãi</th>
                            <th>Loại giảm</th>
                            <th>Giá trị giảm</th>
                            <th>Đơn tối thiểu</th>
                            <th>Lượt sử dụng</th>
                            <th>Ngày bắt đầu</th>
                            <th>Ngày kết thúc</th>
                            <th style="text-align:center;">Trạng thái</th>
                            <th style="text-align:center;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($promotions)): ?>
                            <?php foreach ($promotions as $p): ?>
                                <?php
                                $status = $p['computed_status'] ?? 'expired';
                                ?>
                                <tr>
                                    <td><strong>#<?= (int)$p['promotion_id'] ?></strong></td>
                                    <td><?= htmlspecialchars($p['code']) ?></td>
                                    <td><?= htmlspecialchars($p['name'] ?? '') ?></td>
                                    <td><?= ($p['discount_type'] ?? 'percent') === 'fixed' ? 'VNĐ' : '%' ?></td>
                                    <td>
                                        <?= number_format((float)$p['discount_value'], 2, ',', '.') ?>
                                        <?= ($p['discount_type'] ?? 'percent') === 'fixed' ? ' VNĐ' : ' %' ?>
                                    </td>
                                    <td><?= number_format((float)($p['min_amount'] ?? 0), 2, ',', '.') ?> VNĐ</td>
                                    <td>
                                        <?= (int)($p['usage_count'] ?? 0) ?> /
                                        <?= (int)($p['usage_limit'] ?? 0) > 0 ? (int)$p['usage_limit'] : 'Không giới hạn' ?>
                                    </td>
                                    <td><?= !empty($p['start_date']) ? date('d/m/Y', strtotime($p['start_date'])) : '' ?></td>
                                    <td><?= !empty($p['end_date']) ? date('d/m/Y', strtotime($p['end_date'])) : '' ?></td>
                                    <td style="text-align:center;">
                                        <?php
                                        if ($status === 'active') {
                                            echo '<span style="display:inline-flex;align-items:center;justify-content:center;padding:6px 12px;border-radius:999px;font-size:13px;font-weight:600;background:#16351f;color:#b7f7c8;border:1px solid #2d7a43;">Đang áp dụng</span>';
                                        } elseif ($status === 'scheduled') {
                                            echo '<span style="display:inline-flex;align-items:center;justify-content:center;padding:6px 12px;border-radius:999px;font-size:13px;font-weight:600;background:#4a3512;color:#ffd58a;border:1px solid #b37b18;">Sắp diễn ra</span>';
                                        } else {
                                            echo '<span style="display:inline-flex;align-items:center;justify-content:center;padding:6px 12px;border-radius:999px;font-size:13px;font-weight:600;background:#3a1616;color:#ffb3b3;border:1px solid #a94442;">Đã hết hạn</span>';
                                        }
                                        ?>
                                    </td>
                                    <td style="text-align:center; vertical-align: middle;">
                                        <div class="action-group">

                                            <?php if (hasPermission('promotions_update')): ?>

                                                <button class="btn-action edit"
                                                    onclick="openUpdatePromoModal(this)"
                                                    data-promotion-id="<?= (int)$p['promotion_id'] ?>"
                                                    data-code="<?= htmlspecialchars($p['code']) ?>"
                                                    data-name="<?= htmlspecialchars($p['name'] ?? '') ?>"
                                                    data-discount-type="<?= htmlspecialchars($p['discount_type'] ?? 'percent') ?>"
                                                    data-discount-value="<?= htmlspecialchars($p['discount_value'] ?? '') ?>"
                                                    data-min-amount="<?= htmlspecialchars($p['min_amount'] ?? '0') ?>"
                                                    data-usage-limit="<?= htmlspecialchars($p['usage_limit'] ?? '0') ?>"
                                                    data-start-date="<?= !empty($p['start_date']) ? htmlspecialchars(date('Y-m-d', strtotime($p['start_date']))) : '' ?>"
                                                    data-end-date="<?= !empty($p['end_date']) ? htmlspecialchars(date('Y-m-d', strtotime($p['end_date']))) : '' ?>">
                                                    Sửa
                                                </button>

                                                <?php if ($status === 'active'): ?>
                                                    <button class="btn-action warning"
                                                        onclick="confirmEndPromo(this)"
                                                        data-promotion-id="<?= (int)$p['promotion_id'] ?>"
                                                        data-code="<?= htmlspecialchars($p['code']) ?>">
                                                        Kết thúc sớm
                                                    </button>
                                                <?php endif; ?>

                                            <?php endif; ?>

                                            <?php if (hasPermission('promotions_delete')): ?>
                                                <button class="btn-action danger"
                                                    onclick="confirmDeletePromo(this)"
                                                    data-promotion-id="<?= (int)$p['promotion_id'] ?>">
                                                    Xóa
                                                </button>
                                            <?php endif; ?>

                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" style="text-align:center; padding:20px;">
                                    Chưa có khuyến mãi nào.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ADD MODAL -->
    <?php if (hasPermission('promotions_create')): ?>
        <div id="addPromoModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Thêm khuyến mãi mới</h2>
                    <button class="btn-close" onclick="closeModal('addPromoModal')">&times;</button>
                </div>

                <form id="addPromoForm" action="../admin/index.php?page=promotions&action=create" method="POST" onsubmit="return validatePromotionForm(this)">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Mã Code <span style="color:#ff6b6b">*</span></label>
                            <input type="text" name="code" required maxlength="50" placeholder="VD: GIAM10">
                        </div>

                        <div class="form-group">
                            <label>Tên khuyến mãi <span style="color:#ff6b6b">*</span></label>
                            <input type="text" name="name" required maxlength="150" placeholder="VD: Giảm cuối tuần">
                        </div>

                        <div class="form-group">
                            <label>Đơn vị giảm giá <span style="color:#ff6b6b">*</span></label>
                            <select name="discount_type" required>
                                <option value="percent">%</option>
                                <option value="fixed">VNĐ</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Giá trị giảm <span style="color:#ff6b6b">*</span></label>
                            <input type="number" name="discount_value" min="1" step="0.01" required placeholder="VD: 10 hoặc 5000.50">
                        </div>

                        <div class="form-group">
                            <label>Đơn tối thiểu (VNĐ)</label>
                            <input type="number" name="min_amount" min="0" step="0.01" value="0" placeholder="VD: 50000">
                        </div>

                        <div class="form-group">
                            <label>Giới hạn lượt dùng</label>
                            <input type="number" name="usage_limit" min="0" step="1" value="0" placeholder="0 = không giới hạn">
                            <small style="color:#888;">Nhập 0 nếu không giới hạn, hoặc nhập 10, 20, 30...</small>
                        </div>

                        <div class="form-group">
                            <label>Ngày bắt đầu <span style="color:#ff6b6b">*</span></label>
                            <input type="date" name="start_date" required min="1900-01-01" max="2100-12-31">
                        </div>

                        <div class="form-group">
                            <label>Ngày kết thúc <span style="color:#ff6b6b">*</span></label>
                            <input type="date" name="end_date" required min="1900-01-01" max="2100-12-31">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-action" onclick="closeModal('addPromoModal')">Hủy</button>
                        <button type="submit" class="btn-primary">Thêm</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- UPDATE MODAL -->
    <?php if (hasPermission('promotions_update')): ?>
        <div id="updatePromoModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Cập nhật khuyến mãi</h2>
                    <button class="btn-close" onclick="closeModal('updatePromoModal')">&times;</button>
                </div>

                <form id="updatePromoForm" action="../admin/index.php?page=promotions&action=update" method="POST" onsubmit="return validatePromotionForm(this)">
                    <div class="modal-body">
                        <input type="hidden" name="promotion_id" id="update_promotion_id" value="">

                        <div class="form-group">
                            <label>Mã Code <span style="color:#ff6b6b">*</span></label>
                            <input type="text" name="code" id="update_code" required maxlength="50">
                        </div>

                        <div class="form-group">
                            <label>Tên khuyến mãi <span style="color:#ff6b6b">*</span></label>
                            <input type="text" name="name" id="update_name" required maxlength="150">
                        </div>

                        <div class="form-group">
                            <label>Đơn vị giảm giá <span style="color:#ff6b6b">*</span></label>
                            <select name="discount_type" id="update_discount_type" required>
                                <option value="percent">%</option>
                                <option value="fixed">VNĐ</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Giá trị giảm <span style="color:#ff6b6b">*</span></label>
                            <input type="number" name="discount_value" id="update_discount_value" min="1" step="0.01" required>
                        </div>

                        <div class="form-group">
                            <label>Đơn tối thiểu (VNĐ)</label>
                            <input type="number" name="min_amount" id="update_min_amount" min="0" step="0.01">
                        </div>

                        <div class="form-group">
                            <label>Giới hạn lượt dùng</label>
                            <input type="number" name="usage_limit" id="update_usage_limit" min="0" step="1">
                            <small style="color:#888;">Nhập 0 nếu không giới hạn, hoặc nhập 10, 20, 30...</small>
                        </div>

                        <div class="form-group">
                            <label>Ngày bắt đầu <span style="color:#ff6b6b">*</span></label>
                            <input type="date" name="start_date" id="update_start_date" required min="1900-01-01" max="2100-12-31">
                        </div>

                        <div class="form-group">
                            <label>Ngày kết thúc <span style="color:#ff6b6b">*</span></label>
                            <input type="date" name="end_date" id="update_end_date" required min="1900-01-01" max="2100-12-31">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-action" onclick="closeModal('updatePromoModal')">Hủy</button>
                        <button type="submit" class="btn-primary">Cập nhật</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

</section>

<script>
    function confirmEndPromo(btn) {
        const id = btn.dataset.promotionId;
        const code = btn.dataset.code;
        if (!confirm('Bạn có chắc muốn kết thúc sớm mã "' + code + '"?\nMã sẽ chuyển sang trạng thái Đã hết hạn và không thể dùng được nữa.')) return;
        window.location.href = window.location.pathname + '?page=promotions&action=end&id=' + id;
    }
</script>
