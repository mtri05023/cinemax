<?php
require_once __DIR__ . '/../../../config/dbConfig.php';
require_once __DIR__ . '/../../../models/SeatType.php';
require_once __DIR__ . '/../../../services/SeatTypeService.php';

$conn = getDBConnection();
$seatTypeModel = new SeatType($conn);
$seatTypeService = new SeatTypeService($seatTypeModel);

$seatTypes = [];
try {
    $seatTypes = $seatTypeService->getAllSeatTypes();
} catch (Exception $e) {
    $seatTypes = [];
}

// Giá gốc để tính phụ thu hiển thị
$basePrice = 100000;
?>

<section class="seat_types">

    <header class="admin-header">
        <h1>Quản lý Loại ghế & Giá vé</h1>
        <?php if (hasPermission('seat_types_create')): ?>
            <button class="btn-add" onclick="openModal('addSeatTypeModal')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Thêm loại ghế</span>
            </button>
        <?php endif; ?>
    </header>

    <div class="dashboard-content">
        <div class="dashboard-card">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên loại ghế</th>
                            <th>Phụ thu (VNĐ)</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($seatTypes)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 20px;">
                                    Chưa có loại ghế nào.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($seatTypes as $seatType): ?>
                                <?php
                                $multiplier = (float)($seatType['PriceMultiplier'] ?? $seatType['price_multiplier'] ?? 1);
                                $surcharge = (float)(($multiplier - 1) * $basePrice);
                                $seatTypeId = $seatType['SeatTypeID'] ?? $seatType['seat_type_id'] ?? 0;
                                ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($seatTypeId); ?></td>
                                    <td><strong><?php echo htmlspecialchars($seatType['TypeName'] ?? $seatType['type_name'] ?? ''); ?></strong></td>
                                    <td style="color: var(--success-color); font-weight: bold;">
                                        +<?php echo number_format($surcharge, 2, ',', '.'); ?> ₫
                                    </td>
                                    <td>
                                        <?php if (hasPermission('seat_types_update')): ?>
                                            <a href="#"
                                                class="btn-action"
                                                onclick="editSeatType(<?php echo (int)$seatTypeId; ?>); return false;">
                                                Sửa
                                            </a>
                                        <?php endif; ?>

                                        <?php if (hasPermission('seat_types_delete')): ?>
                                            <button class="btn-action danger"
                                                type="button"
                                                onclick="deleteSeatType(<?php echo (int)$seatTypeId; ?>)">
                                                Xóa
                                            </button>
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

    <div id="addSeatTypeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Thêm loại ghế</h2>
                <button class="btn-close" onclick="closeModal('addSeatTypeModal')">&times;</button>
            </div>
            <form id="addSeatTypeForm" onsubmit="handleCreateSeatType(event); return false;">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Tên loại ghế</label>
                        <input type="text" name="type_name" placeholder="VD: VIP, Couple" required>
                    </div>
                    <div class="form-group">
                        <label>Phụ thu (VNĐ)</label>
                        <input type="number" name="price_surcharge" value="0" required step="0.01" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-action" onclick="closeModal('addSeatTypeModal')">Hủy</button>
                    <button type="submit" class="btn-primary">Thêm</button>
                </div>
            </form>
        </div>
    </div>
</section>

<script src="../../public/assets/js/api.js"></script>
<script src="../../public/assets/js/seat-types-admin.js"></script>