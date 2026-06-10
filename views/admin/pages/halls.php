<?php
require_once __DIR__ . '/../../../config/dbConfig.php';
require_once __DIR__ . '/../../../models/Hall.php';
require_once __DIR__ . '/../../../models/Cinema.php';
require_once __DIR__ . '/../../../services/HallService.php';

$conn = getDBConnection();

$hallModel = new Hall($conn);
$hallService = new HallService($hallModel);

// Danh sách rạp cho filter & modal
$cinemas = [];
$cinemaResult = $conn->query("SELECT cinema_id as CinemaID, name as Name, status as Status FROM cinemas ORDER BY name");
if ($cinemaResult) {
    while ($row = $cinemaResult->fetch_assoc()) {
        $cinemas[] = $row;
    }
}

// Danh sách trạng thái phòng
$statuses = [
    ['StatusID' => 1, 'StatusName' => 'Đang hoạt động'],
    ['StatusID' => 0, 'StatusName' => 'Tạm dừng'],
    ['StatusID' => 2, 'StatusName' => 'Bảo trì']
];

// Lọc theo rạp
$selectedCinemaId = $_GET['cinema_id'] ?? null;
if ($selectedCinemaId === '') {
    $selectedCinemaId = null;
}

// Danh sách phòng chiếu
$halls = [];
try {
    $halls = $hallService->getAllHalls($selectedCinemaId);
    foreach ($halls as &$hall) {
        $hallId = $hall['hall_id'] ?? $hall['HallID'] ?? 0;
        $hall['SeatCount'] = $hallService->getSeatCount($hallId);
    }
    unset($hall);
} catch (Exception $e) {
    $halls = [];
}
?>

<section class="halls">

    <header class="admin-header">
        <h1>Quản lý Phòng chiếu</h1>
        <?php if (hasPermission('halls_create')): ?>
            <button class="btn-add" onclick="openModal('addScreenModal')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Thêm phòng mới</span>
            </button>
        <?php endif; ?>
    </header>

    <div class="dashboard-content">
        <div class="dashboard-card">
            <form method="GET" class="filter-bar" id="filterForm">
                <input type="hidden" name="page" value="halls">
                <select name="cinema_id" class="filter-select" id="cinemaFilter">
                    <option value="">-- Tất cả các rạp --</option>
                    <?php foreach ($cinemas as $cinema): ?>
                        <option value="<?php echo htmlspecialchars($cinema['CinemaID']); ?>"
                            <?php echo ($selectedCinemaId && (string)$selectedCinemaId === (string)$cinema['CinemaID']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cinema['Name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-action">Lọc</button>
                <a href="index.php?page=halls" class="btn-action">Xóa lọc</a>
            </form>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên Rạp</th>
                            <th>Tên Phòng</th>
                            <th>Trạng thái</th>
                            <th>Sức chứa</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($halls)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 20px;">
                                    Chưa có phòng chiếu nào.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($halls as $hall): ?>
                                <?php $isHallActive = (int)($hall['status'] ?? $hall['Status'] ?? 0) === 1; ?>
                                <tr style="<?php echo $isHallActive ? '' : 'opacity: 0.45;'; ?>">
                                    <td>#<?php echo htmlspecialchars($hall['hall_id'] ?? $hall['HallID'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($hall['CinemaName'] ?? ''); ?></td>
                                    <td><strong><?php echo htmlspecialchars($hall['name'] ?? $hall['Name'] ?? ''); ?></strong></td>
                                    <td>
                                        <?php
                                        $statusName = $hall['StatusName'] ?? '';
                                        $statusColor = '#666';
                                        if (stripos($statusName, 'hoạt động') !== false) {
                                            $statusColor = '#46d369';
                                        } elseif (stripos($statusName, 'bảo trì') !== false) {
                                            $statusColor = '#ffa500';
                                        } elseif (stripos($statusName, 'tạm dừng') !== false) {
                                            $statusColor = '#e50914';
                                        }
                                        ?>
                                        <span style="color: <?php echo $statusColor; ?>; font-weight: bold;">
                                            <?php echo htmlspecialchars($statusName); ?>
                                        </span>
                                    </td>
                                    <td><?php echo (int)($hall['SeatCount'] ?? 0); ?> ghế</td>
                                    <td>
                                        <?php $hallId = $hall['hall_id'] ?? $hall['HallID'] ?? 0; ?>
                                        <?php if (hasPermission('halls_update')): ?>
                                            <a href="index.php?page=seats&hall_id=<?php echo urlencode($hallId); ?>"
                                                class="btn-action"
                                                style="color: #46d369; border-color: #46d369;"
                                                title="Cấu hình ghế">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                    <path d="M3 9h18"></path>
                                                    <path d="M9 21V9"></path>
                                                </svg>
                                                Cấu hình ghế
                                            </a>
                                            <a href="#" class="btn-action"
                                                onclick="editHall(<?php echo (int)$hallId; ?>); return false;"
                                                title="Sửa">Sửa</a>
                                        <?php endif; ?>
                                        <?php if (hasPermission('halls_delete')): ?>
                                            <button class="btn-action danger"
                                                onclick="deleteHallHandler(<?php echo (int)$hallId; ?>)"
                                                type="button"
                                                title="Xóa">Xóa</button>
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

    <div id="addScreenModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Thêm phòng chiếu</h2>
                <button class="btn-close" onclick="closeModal('addScreenModal')">&times;</button>
            </div>
            <form id="addHallForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Chọn Rạp</label>
                        <select name="cinema_id" id="addCinemaId" required>
                            <option value="">-- Chọn rạp --</option>
                            <?php foreach ($cinemas as $cinema): ?>
                                <option value="<?php echo htmlspecialchars($cinema['CinemaID']); ?>">
                                    <?php echo htmlspecialchars($cinema['Name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tên phòng</label>
                        <input type="text" name="name" placeholder="VD: Phòng 1, Phòng IMAX" required>
                    </div>
                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select name="status_id" id="addStatusId" required>
                            <option value="">-- Chọn trạng thái --</option>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?php echo htmlspecialchars($status['StatusID'] ?? $status['status_id'] ?? ''); ?>">
                                    <?php echo htmlspecialchars($status['StatusName'] ?? $status['status_name'] ?? ''); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Số lượng ghế</label>
                        <input type="number" name="seat_count" min="1" max="500" value="120" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-action" onclick="closeModal('addScreenModal')">Hủy</button>
                    <button type="submit" class="btn-primary">Thêm</button>
                </div>
            </form>
        </div>
    </div>
</section>

<script src="../../public/assets/js/api.js"></script>
<script src="../../public/assets/js/halls-admin.js"></script>