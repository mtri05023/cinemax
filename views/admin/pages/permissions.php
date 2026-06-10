<?php
$permissions = $adminController->getAllPermissions();
$roles = $adminController->getAllRoles();
$rolePermissions = $rolePermissionsController->getAllRolePermissions();
$error = $_GET['error'] ?? null;

$moduleNames = [
    'dashboard' => 'Thống kê',
    'movies' => 'Phim',
    'users' => 'Người dùng',
    'roles' => 'Vai trò',
    'permissions' => 'Quyền'
];

$actionNames = [
    'view' => 'Xem',
    'create' => 'Thêm',
    'update' => 'Sửa',
    'delete' => 'Xóa'
];

unset($_GET['error']);
?>
<section class="permissions">
    <header class="admin-header">
        <h1>Quản lý quyền</h1>
        <div class="header-actions">
            <?php if (hasPermission('permissions_create')): ?>
                <button class="btn-add" onclick="openModal('addPermissionModal')">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Thêm quyền</span>
                </button>
            <?php endif; ?>
        </div>
    </header>

    <div class="dashboard-content">

        <!-- ALERT -->
        <?php if ($error || isset($_GET['add']) || isset($_GET['update']) || isset($_GET['delete']) || isset($_GET['save'])): ?>
            <div class="alert <?= $error ? 'alert-error' : 'alert-success' ?>" id="autoAlert">
                <?php
                if ($error) {
                    echo htmlspecialchars($error);
                }

                if (isset($_GET['add']) && $_GET['add'] == 1) {
                    echo "Quyền đã được thêm thành công!";
                } elseif (isset($_GET['update']) && $_GET['update'] == 1) {
                    echo "Quyền đã được cập nhật thành công!";
                } elseif (isset($_GET['delete']) && $_GET['delete'] == 1) {
                    echo "Quyền đã được xóa thành công!";
                } elseif (isset($_GET['save']) && $_GET['save'] == 1) {
                    echo "Phân quyền đã được lưu thành công!";
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-card">
            <div class="table-responsive">
                <form method="POST" action="../admin/index.php?page=permissions&action=saveRolePermissions">
                    <table class="data-table permission-matrix">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Chức năng</th>

                                <?php foreach ($roles as $role): ?>
                                    <th style="text-align: center;"><?= htmlspecialchars($role['role_name']) ?></th>
                                <?php endforeach; ?>

                                <th style="text-align: center;">Hành động</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($permissions as $permission): ?>
                                <tr>
                                    <td><strong>#<?= htmlspecialchars($permission['permission_id']) ?></strong></td>
                                    <td>
                                        <?php list($module, $action) = explode('_', $permission['permission_code']); ?>
                                        <?= $actionNames[$action] ?? $action ?>
                                        <?= $moduleNames[$module] ?? $module ?>
                                    </td>

                                    <?php foreach ($roles as $role): ?>
                                        <td>
                                            <input type="checkbox"
                                                name="role_permissions[<?= $role['role_id'] ?>][]"
                                                value="<?= $permission['permission_id'] ?>"
                                                <?= isset($rolePermissions[$role['role_id']]) &&
                                                    in_array($permission['permission_id'], $rolePermissions[$role['role_id']])
                                                    ? 'checked' : '' ?>>
                                        </td>
                                    <?php endforeach; ?>

                                    <td>
                                        <?php if (hasPermission('permissions_update')): ?>
                                            <button class="btn-action" type="button"
                                                onclick="openUpdatePermissionModal(this)"
                                                data-permission-id="<?= $permission['permission_id'] ?>"
                                                data-permission-code="<?= htmlspecialchars($permission['permission_code']) ?>"
                                                data-permission-description="<?= htmlspecialchars($permission['description']) ?>">
                                                <i class="fas fa-edit" style="color: #007BFF;"></i>
                                            </button>
                                        <?php endif; ?>

                                        <?php if (hasPermission('permissions_delete')): ?>
                                            <button type="button" class="btn-action" onclick="confirmDeletePermission(<?= htmlspecialchars($permission['permission_id']) ?>)">
                                                <i class="fas fa-times" style="color: #E50914;"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if (hasPermission('permissions_update')): ?>
                        <div style="margin-top:20px; text-align:right;">
                            <button class="btn-primary" type="submit">Lưu phân quyền</button>
                        </div>
                    <?php endif; ?>
                </form>

            </div>
        </div>

    </div>

    <!-- ADD PERMISSION MODAL -->
    <div id="addPermissionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Thêm quyền mới</h2>
                <button class="btn-close" onclick="closeModal('addPermissionModal')">&times;</button>
            </div>

            <form id="addPermissionForm" action="../admin/index.php?page=permissions&action=create" method="POST">
                <div class="modal-body">

                    <!-- MODULE -->
                    <div class="form-group">
                        <label>Chức năng</label>
                        <select name="module" required>
                            <option value="">-- Chọn chức năng --</option>
                            <option value="dashboard">Thống kê</option>

                            <option value="movies">Danh sách phim</option>
                            <option value="movie_genres">Thể loại phim</option>

                            <option value="cinemas">Rạp chiếu</option>
                            <option value="halls">Phòng chiếu</option>
                            <option value="seat_types">Loại ghế & Giá</option>

                            <option value="showtimes">Lịch chiếu</option>
                            <option value="bookings">Đơn đặt vé</option>

                            <option value="foods">Đồ ăn & Combo</option>
                            <option value="promotions">Khuyến mãi</option>

                            <option value="roles">Vai trò</option>
                            <option value="permissions">Quyền</option>
                            <option value="users">Người dùng</option>
                        </select>
                    </div>

                    <!-- ACTION -->
                    <div class="form-group">
                        <label>Hành động</label>
                        <select name="action" required>
                            <option value="">-- Chọn hành động --</option>
                            <option value="view">Xem</option>
                            <option value="create">Thêm</option>
                            <option value="update">Sửa</option>
                            <option value="delete">Xóa</option>
                        </select>
                    </div>

                    <!-- DESCRIPTION -->
                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea name="description" placeholder="Mô tả quyền (tùy chọn)"></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-action"
                            onclick="closeModal('addPermissionModal')">Hủy</button>
                        <button type="submit" class="btn-primary">Thêm quyền</button>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <!-- UPDATE PERMISSION MODAL -->
    <div id="updatePermissionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Cập nhật quyền</h2>
                <button class="btn-close" onclick="closeModal('updatePermissionModal')">&times;</button>
            </div>

            <form id="updatePermissionForm" action="../admin/index.php?page=permissions&action=update" method="POST">
                <div class="modal-body">

                    <input type="hidden" id="update_permission_id" name="permission_id">

                    <!-- MODULE -->
                    <div class="form-group">
                        <label>Module</label>
                        <select id="update_module" name="module" required>
                            <option value="dashboard">Thống kê</option>

                            <option value="movies">Danh sách phim</option>
                            <option value="movie_genres">Thể loại phim</option>

                            <option value="cinemas">Rạp chiếu</option>
                            <option value="halls">Phòng chiếu</option>
                            <option value="seat_types">Loại ghế & Giá</option>

                            <option value="showtimes">Lịch chiếu</option>
                            <option value="bookings">Đơn đặt vé</option>

                            <option value="foods">Đồ ăn & Combo</option>
                            <option value="promotions">Khuyến mãi</option>

                            <option value="roles">Vai trò</option>
                            <option value="permissions">Quyền</option>
                            <option value="users">Người dùng</option>
                        </select>
                    </div>

                    <!-- ACTION -->
                    <div class="form-group">
                        <label>Hành động</label>
                        <select id="update_action" name="action" required>
                            <option value="view">Xem</option>
                            <option value="create">Thêm</option>
                            <option value="update">Sửa</option>
                            <option value="delete">Xóa</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea id="update_permission_description" name="description"></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-action"
                            onclick="closeModal('updatePermissionModal')">Hủy</button>
                        <button type="submit" class="btn-primary">Cập nhật quyền</button>
                    </div>

                </div>
            </form>
        </div>
    </div>
</section>