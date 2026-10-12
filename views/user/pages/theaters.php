<?php
require_once __DIR__ . '/../../../config/dbConfig.php';
require_once __DIR__ . '/../../../models/Cinema.php';

$conn = getDBConnection();
$cinemaModel = new Cinema($conn);

// Lấy danh sách khu vực (locations)
$locations = [];
try {
    $locations = $cinemaModel->getAllLocations();
} catch (Exception $e) {
    $locations = [];
}

// Lấy filter khu vực từ query string
$selectedLocationId = $_GET['location_id'] ?? '';

// Lấy danh sách rạp (chỉ hiển thị rạp đang hoạt động cho user)
$cinemas = [];
try {
    $cinemas = $cinemaModel->getAllCinemas();
} catch (Exception $e) {
    $cinemas = [];
}

// Chỉ giữ rạp có Status = 1
if (!empty($cinemas)) {
    $cinemas = array_filter($cinemas, function ($cinema) {
        $status = $cinema['status'] ?? $cinema['Status'] ?? 1;
        return (int)$status === 1;
    });
}

// Lọc theo khu vực nếu có chọn
if ($selectedLocationId !== '' && !empty($cinemas)) {
    $cinemas = array_filter($cinemas, function ($cinema) use ($selectedLocationId) {
        $locId = $cinema['location_id'] ?? $cinema['LocationID'] ?? null;
        return $locId !== null && (string)$locId === (string)$selectedLocationId;
    });
}
?>

<main class="section">
    <div class="container">
        <div class="section-header">
            <h2>Hệ thống rạp Cinemax</h2>
        </div>

        <!-- Bộ lọc khu vực / thành phố (render trực tiếp bằng PHP) -->
        <form method="GET" class="filter-bar" style="margin-bottom: 20px; display: flex; gap: 12px; align-items: center;">
            <input type="hidden" name="page" value="theaters">
            <label for="locationFilter" style="font-weight: 500;">Khu vực:</label>
            <select name="location_id" id="locationFilter"
                    style="padding: 8px 12px; border-radius: 4px; border: 1px solid #444; background: #111; color: #fff;">
                <option value="">Tất cả khu vực</option>
                <?php foreach ($locations as $location): ?>
                    <option value="<?php echo htmlspecialchars($location['LocationID']); ?>"
                        <?php echo ($selectedLocationId !== '' && (string)$selectedLocationId === (string)$location['LocationID']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($location['Name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-primary" style="padding: 8px 16px;">Lọc</button>
        </form>

        <div class="theater-list">
            <?php if (empty($cinemas)): ?>
                <div style="text-align: center; padding: 40px; color: #888;">
                    <?php if ($selectedLocationId !== ''): ?>
                        Không có rạp nào trong khu vực này.
                    <?php else: ?>
                        Chưa có rạp chiếu nào.
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($cinemas as $cinema): ?>
                    <div class="theater-card">
                        <h3><?php echo htmlspecialchars($cinema['name'] ?? $cinema['Name'] ?? ''); ?></h3>
                        <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($cinema['address'] ?? $cinema['Address'] ?? ''); ?></p>
                        <p><strong>Khu vực:</strong> <?php echo htmlspecialchars($cinema['LocationName'] ?? ''); ?></p>
                        <p><strong>Số phòng:</strong> <?php echo (int)($cinema['HallCount'] ?? 0); ?></p>
                        <?php $cinemaId = $cinema['cinema_id'] ?? $cinema['CinemaID'] ?? ''; ?>
                        <a href="index.php?page=showtimes&cinema_id=<?php echo urlencode($cinemaId); ?>" class="btn-primary">
                            Xem lịch chiếu
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>