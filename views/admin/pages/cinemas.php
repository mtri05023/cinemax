<section class="theaters">

    <header class="admin-header">
        <h1>Quản lý rạp chiếu</h1>
        <div class="header-actions">
            <?php if (hasPermission('cinemas_create')): ?>
            <button class="btn-add" onclick="openModal('addTheaterModal')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Thêm rạp mới</span>
            </button>
            <?php endif; ?>
        </div>
    </header>

    <div class="dashboard-content">

        <!-- TABLE LIST -->
        <div class="dashboard-card">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên rạp</th>
                        <th>Địa chỉ</th>
                        <th>Thành phố</th>
                        <th>Số phòng</th>
                        <th>Hành động</th>
                    </tr>
                    </thead>
                    <tbody id="theatersTableBody">
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px;">
                                <div class="loading">Đang tải dữ liệu...</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- MODAL ADD THEATER -->
    <div id="addTheaterModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Thêm rạp mới</h2>
                <button class="btn-close" onclick="closeModal('addTheaterModal')">&times;</button>
            </div>

            <form id="addCinemaForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Tên rạp</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Địa chỉ</label>
                        <input type="text" name="address" required>
                    </div>
                    <div class="form-group">
                        <label>Thành phố</label>
                        <select name="location_id" id="addLocationId" required>
                            <option value="">-- Chọn thành phố --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select name="status_id" id="addStatusId" required>
                            <option value="">-- Chọn trạng thái --</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-action" onclick="closeModal('addTheaterModal')">
                        Hủy
                    </button>
                    <button type="submit" class="btn-primary">
                        Thêm rạp
                    </button>
                </div>
            </form>
        </div>
    </div>

</section>

<?php
    $apiJsV = @filemtime(__DIR__ . '/../../../public/assets/js/api.js') ?: time();
    $cinemasAdminJsV = @filemtime(__DIR__ . '/../../../public/assets/js/cinemas-admin.js') ?: time();
?>
<script src="../../public/assets/js/api.js?v=<?php echo urlencode((string)$apiJsV); ?>"></script>
<script src="../../public/assets/js/cinemas-admin.js?v=<?php echo urlencode((string)$cinemasAdminJsV); ?>"></script>
