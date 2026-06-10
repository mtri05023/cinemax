<?php
// Sử dụng $foodComboService đã được khởi tạo trong admin/index.php
// Nếu vì lý do nào đó chưa có, fallback tự khởi tạo (tránh lỗi khi gọi trực tiếp)
if (!isset($foodComboService)) {
    require_once __DIR__ . '/../../../config/dbConfig.php';
    require_once __DIR__ . '/../../../models/FoodCombo.php';
    require_once __DIR__ . '/../../../services/FoodComboService.php';
    $conn = getDBConnection();
    $foodComboService = new FoodComboService(new FoodCombo($conn));
}

$error = $_SESSION['error'] ?? null;
$success = $_SESSION['success'] ?? null;
unset($_SESSION['error'], $_SESSION['success']);

$combos = [];
try {
    $combos = $foodComboService->getAllCombos();
} catch (Exception $e) {
    $combos = [];
}

// Nếu có ?edit_id=... thì lấy combo đó để hiển thị trong modal sửa
$editComboId = $_GET['edit_id'] ?? null;
$editCombo = null;
if ($editComboId) {
    try {
        $editCombo = $foodComboService->getComboById($editComboId);
    } catch (Exception $e) {
        $editCombo = null;
    }
}

?>

<section class="combos">

    <header class="admin-header">
        <h1>Quản lý Đồ ăn & Combo</h1>
        <?php if (hasPermission('combos_create')): ?>
        <div class="header-actions">
            <button class="btn-add" onclick="openModal('addFoodModal')">
                <span>+ Thêm món mới</span>
            </button>
        </div>
        <?php endif; ?>
    </header>

    <div class="dashboard-content">
        <?php if ($error || $success || isset($_GET['add']) || isset($_GET['update']) || isset($_GET['delete']) || isset($_GET['error'])): ?>
            <div class="alert <?= $error ? 'alert-error' : 'alert-success' ?>" id="autoAlert">
                <?php
                if ($error) {
                    echo htmlspecialchars($error);
                } elseif ($success) {
                    echo htmlspecialchars($success);
                } elseif (isset($_GET['add']) && $_GET['add'] == 1) {
                    echo "Thêm combo thành công!";
                } elseif (isset($_GET['update']) && $_GET['update'] == 1) {
                    echo "Cập nhật combo thành công!";
                } elseif (isset($_GET['delete']) && $_GET['delete'] == 1) {
                    echo "Đóng/Xóa combo thành công!";
                } elseif (isset($_GET['error']) && $_GET['error'] == 1) {
                    echo "Có lỗi xảy ra. Vui lòng thử lại.";
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-card">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Hình ảnh</th>
                            <th>Tên món / combo</th>
                            <th>Giá</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($combos)): ?>
                            <tr>
                                <td colspan="5" class="text-center">Chưa có món nào.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($combos as $combo): ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($combo['combo_id']); ?></td>
                                    <td>
                                        <?php if (!empty($combo['image_url'])): ?>
                                            <img
                                                src="../../<?php echo htmlspecialchars($combo['image_url']); ?>"
                                                alt="<?php echo htmlspecialchars($combo['name']); ?>"
                                                style="width:50px;height:50px;object-fit:cover;border-radius:8px;"
                                            >
                                        <?php else: ?>
                                            <div style="width: 50px; height: 50px; background: #333; border-radius: 8px;"></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($combo['name']); ?></strong><br>
                                        <small style="color:#aaa;"><?php echo htmlspecialchars($combo['description'] ?? ''); ?></small>
                                    </td>
                                    <td>
                                        <?php echo number_format((float)$combo['price'], 0, ',', '.'); ?> ₫
                                    </td>
                                    <td>
                                        <?php if (hasPermission('combos_update')): ?>
                                        <a href="index.php?page=combos&edit_id=<?php echo urlencode($combo['combo_id']); ?>"
                                           class="btn-action">
                                            Sửa
                                        </a>
                                        <?php endif; ?>

                                        <?php if (hasPermission('combos_delete')): ?>
                                        <a href="index.php?page=combos&action=delete&id=<?php echo urlencode($combo['combo_id']); ?>"
                                           class="btn-action danger"
                                           onclick="return confirm('Xóa combo này?');">
                                            Xóa
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div id="addFoodModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Thêm món mới</h2>
                <button class="btn-close" onclick="closeModal('addFoodModal')">&times;</button>
            </div>

            <form id="addFoodForm"
                  method="POST"
                  action="index.php?page=combos&action=create"
                  enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Tên món / combo</label>
                        <input type="text" name="name" required class="custom-input">
                    </div>

                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea name="description" rows="3" maxlength="70"
                            class="custom-input combo-description"></textarea>
                        <small class="description-counter">0/70 ký tự</small>
                    </div>

                    <div class="form-group">
                        <label>Giá (VNĐ)</label>
                        <input type="number" name="price" required step="1000" min="0" class="custom-input">
                    </div>

                    <div class="form-group">
                        <label>Hình ảnh</label>
                        <input type="file" name="image_file" accept="image/*" class="custom-input">
                        <small style="color:#888;">(Ảnh sẽ được lưu tại public/assets/uploads/combos)</small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-action" onclick="closeModal('addFoodModal')">Hủy</button>
                    <button type="submit" class="btn-primary">Thêm món</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($editCombo): ?>
        <div id="editFoodModal" class="modal active">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Cập nhật món / combo</h2>
                    <button class="btn-close" onclick="closeModal('editFoodModal')">&times;</button>
                </div>

                <form id="editFoodForm"
                      method="POST"
                      action="index.php?page=combos&action=update"
                      enctype="multipart/form-data">
                    <input type="hidden" name="combo_id" value="<?php echo htmlspecialchars($editCombo['combo_id']); ?>">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Tên món / combo</label>
                            <input type="text" name="name" required class="custom-input"
                                   value="<?php echo htmlspecialchars($editCombo['name']); ?>">
                        </div>

                        <div class="form-group">
                            <label>Mô tả</label>
                            <textarea name="description" rows="3" maxlength="70"
                                class="custom-input combo-description"><?php echo htmlspecialchars($editCombo['description'] ?? ''); ?></textarea>
                            <small class="description-counter">0/70 ký tự</small>
                        </div>

                        <div class="form-group">
                            <label>Giá (VNĐ)</label>
                            <input type="number" name="price" required step="1000" min="0" class="custom-input"
                                   value="<?php echo htmlspecialchars((float)$editCombo['price']); ?>">
                        </div>

                        <div class="form-group">
                            <label>Hình ảnh</label>
                            <input type="file" name="image_file" accept="image/*" class="custom-input">
                            <small style="color:#888;">(Chọn ảnh mới nếu muốn thay đổi)</small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-action" onclick="closeModal('editFoodModal')">Hủy</button>
                        <button type="submit" class="btn-primary">Cập nhật</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

</section>

<script>
    document.querySelectorAll('.combo-description').forEach(textarea => {
        const counter = textarea.parentElement.querySelector('.description-counter');
        const updateCounter = () => {
            counter.textContent = `${Array.from(textarea.value).length}/70 ký tự`;
        };

        textarea.addEventListener('input', updateCounter);
        updateCounter();
    });
</script>
