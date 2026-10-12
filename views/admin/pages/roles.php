<?php
$roles = $adminController->getAllRoles();

$roleId = $_GET['role_id'] ?? null;
$error = $_GET['error'] ?? null;
?>
<section class="roles">
    <header class="admin-header">
        <h1>Quản lý vai trò</h1>
        <div class="header-actions">
            <?php if (hasPermission('roles_create')): ?>
                <button class="btn-add" onclick="openModal('addRoleModal')">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Thêm vai trò</span>
                </button>
            <?php endif; ?>
        </div>
    </header>

    <div class="dashboard-content">
        <?php if ($error || isset($_GET['add']) || isset($_GET['update']) || isset($_GET['delete'])): ?>
            <div class="alert <?= $error ? 'alert-error' : 'alert-success' ?>" id="autoAlert">
                <?php
                if ($error) {
                    echo htmlspecialchars($error);
                }

                if (isset($_GET['add']) && $_GET['add'] == 1) {
                    echo "Vai trò đã được thêm thành công!";
                } elseif (isset($_GET['update']) && $_GET['update'] == 1) {
                    echo "Vai trò đã được cập nhật thành công!";
                } elseif (isset($_GET['delete']) && $_GET['delete'] == 1) {
                    echo "Vai trò đã được xóa thành công!";
                }
                ?>
            </div>
        <?php endif; ?>
        <!-- TABLE ROLES -->
        <div class="dashboard-card">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên vai trò</th>
                            <th>Mô tả</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($roles)): ?>
                            <?php foreach ($roles as $role): ?>
                                <tr>
                                    <td><strong>#<?= htmlspecialchars($role['role_id']) ?></strong></td>
                                    <td><?= htmlspecialchars($role['role_name']) ?></td>
                                    <td><?= !empty($role['description'])
                                            ? htmlspecialchars($role['description'])
                                            : '-' ?></td>
                                    <td>
                                        <?php if (hasPermission('roles_update') && $role['role_id'] !== 1): ?>
                                            <button class="btn-action"
                                                onclick="openUpdateRoleModal(this)"
                                                data-role-id="<?= htmlspecialchars($role['role_id']) ?>"
                                                data-role-name="<?= htmlspecialchars($role['role_name']) ?>"
                                                data-role-description="<?= htmlspecialchars($role['description']) ?>">
                                                Cập nhật
                                            </button>
                                        <?php endif; ?>

                                        <?php if (hasPermission('roles_delete') && $role['role_id'] !== 1): ?>
                                            <button class="btn-action danger"
                                                onclick="confirmDeleteRole(this)"
                                                data-role-id="<?= htmlspecialchars($role['role_id']) ?>">Xóa
                                            </button>
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

                        <!-- EMPTY STATE -->
                        <!--
                        <tr>
                            <td colspan="6" style="text-align:center; padding:20px;">
                                Chưa có vai trò nào.
                            </td>
                        </tr>
                        -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ADD ROLE MODAL -->
    <div id="addRoleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Thêm vai trò mới</h2>
                <button class="btn-close" onclick="closeModal('addRoleModal')">&times;</button>
            </div>

            <form id="addRoleForm" action="../admin/index.php?page=roles&action=create" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Tên vai trò</label>
                        <input type="text" name="role_name" placeholder="VD: Admin, Editor..." required>
                    </div>

                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea name="description" rows="3"
                            placeholder="Mô tả chức năng của vai trò"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-action"
                        onclick="closeModal('addRoleModal')">Hủy</button>
                    <button type="submit" class="btn-primary">Thêm vai trò</button>
                </div>
            </form>
        </div>
    </div>

    <!-- UPDATE ROLE MODAL -->
    <div id="updateRoleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Cập nhật vai trò</h2>
                <button class="btn-close" onclick="closeModal('updateRoleModal')">&times;</button>
            </div>

            <form id="updateRoleForm" action="../admin/index.php?page=roles&action=update" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <input type="hidden" name="role_id" id="update_role_id" value="">
                        <label>Tên vai trò</label>
                        <input type="text" name="role_name" id="update_role_name" value="" required>
                    </div>

                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea name="description" rows="3"
                            placeholder="Mô tả chức năng của vai trò" id="update_role_description">
                        </textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-action"
                        onclick="closeModal('updateRoleModal')">Hủy</button>
                    <button type="submit" class="btn-primary">Cập nhật vai trò</button>
                </div>
            </form>
        </div>
    </div>
</section>