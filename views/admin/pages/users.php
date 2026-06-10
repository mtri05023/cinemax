<?php
$users = $adminController->getAllUsers();
$roles = $adminController->getAllRoles();
$error = $_GET['error'] ?? null;
?>
<section class="users">
    <header class="admin-header">
        <h1>Quản lý người dùng</h1>
        <div class="header-actions">
            <?php if (hasPermission('users_create')): ?>
            <button class="btn-add" onclick="openModal('addUserModal')">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>Thêm người dùng</span>
            </button>
            <?php endif; ?>
        </div>
    </header>

    <div class="dashboard-content">
        <?php if ($error || isset($_GET['add']) || isset($_GET['update']) || isset($_GET['delete'])) : ?>
            <div class="alert <?= $error ? 'alert-danger' : 'alert-success' ?>" id="autoAlert">
                <?php
                if ($error) {
                    echo htmlspecialchars($error);
                }

                if (isset($_GET['add']) && $_GET['add'] == 1) {
                    echo "Người dùng đã được thêm thành công!";
                }

                if (isset($_GET['update']) && $_GET['update'] == 1) {
                    echo "Người dùng đã được cập nhật thành công!";
                }

                if (isset($_GET['delete']) && $_GET['delete'] == 1) {
                    echo "Người dùng đã được xóa thành công!";
                }
                ?>
            </div>
        <?php endif; ?>
        <!-- TABLE USERS -->
        <div class="dashboard-card">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th>SĐT</th>
                            <th>Vai trò</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><strong>#<?= htmlspecialchars($user['user_id']) ?></strong></td>
                                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= htmlspecialchars($user['phone'] ?? '-') ?></td>
                                    <td>
                                        <?php
                                        foreach ($roles as $role) {
                                            if ($user['role_id'] == $role['role_id']) {
                                                echo htmlspecialchars($role['role_name']);
                                                break;
                                            }
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (hasPermission('users_update') && $user['role_id'] != 1): ?>
                                        <button class="btn-action" onclick="openUpdateUserModal(this)"
                                            data-user-id="<?= htmlspecialchars($user['user_id']) ?>"
                                            data-full-name="<?= htmlspecialchars($user['full_name']) ?>"
                                            data-email="<?= htmlspecialchars($user['email']) ?>"
                                            data-phone="<?= htmlspecialchars($user['phone']) ?>"
                                            data-role-id="<?= htmlspecialchars($user['role_id']) ?>">
                                            Cập nhật
                                        </button>
                                        <?php endif; ?>
                                        <?php if (hasPermission('users_delete') && $user['role_id'] != 1): ?>
                                            <button class="btn-action danger" onclick="confirmDeleteUser(this)"
                                                data-user-id="<?= htmlspecialchars($user['user_id']) ?>">Xóa</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding:20px;">
                                    Chưa có người dùng nào trong hệ thống.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Thêm người dùng mới</h2>
                <button class="btn-close" onclick="closeModal('addUserModal')">&times;</button>
            </div>

            <form id="addUserForm" action="../admin/index.php?page=users&action=create" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Họ tên</label>
                        <input type="text" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="tel" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label>Mật khẩu</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Vai trò</label>
                        <?php
                        $roles = $adminController->getAllRoles();
                        ?>
                        <select name="role_id" required>
                            <option value="">Chọn vai trò</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= htmlspecialchars($role['role_id']) ?>">
                                    <?= htmlspecialchars($role['role_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-action" onclick="closeModal('addUserModal')">Hủy</button>
                    <button type="submit" class="btn-primary">Thêm người dùng</button>
                </div>
            </form>
        </div>
    </div>

    <div id="updateUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Cập nhật người dùng</h2>
                <button class="btn-close" onclick="closeModal('updateUserModal')">&times;</button>
            </div>

            <form id="updateUserForm" action="../admin/index.php?page=users&action=update" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <input type="hidden" name="user_id" id="update_user_id" value="">
                        <label>Họ tên</label>
                        <input type="text" name="full_name" id="update_full_name" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="update_email" required>
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="tel" name="phone" id="update_phone" required>
                    </div>
                    <div class="form-group">
                        <label>Vai trò</label>
                        <?php
                        $roles = $adminController->getAllRoles();
                        ?>
                        <select name="role_id" required>
                            <option value="">Chọn vai trò</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= htmlspecialchars($role['role_id']) ?>">
                                    <?= htmlspecialchars($role['role_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-action" onclick="closeModal('updateUserModal')">Hủy</button>
                    <button type="submit" class="btn-primary">Cập nhật người dùng</button>
                </div>
            </form>
        </div>
    </div>
</section>