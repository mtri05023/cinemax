<?php
$q = $_GET['q'] ?? '';
$genres = $genreService->listAdmin($q);
?>

<section class="genres">

    <header class="admin-header">
        <h1>Quản lý Thể loại Phim</h1>
        <?php if (hasPermission('genres_create')): ?>
        <div class="header-actions">
            <button class="btn-add" type="button" onclick="openAddGenreModal()">
                <span>Thêm thể loại</span>
            </button>
        </div>
        <?php endif; ?>
    </header>

    <div class="dashboard-content">
        <div class="dashboard-card">

            <!-- ALERT -->
            <?php if (isset($_GET['add'])): ?>
                <div class="alert alert-success" style="margin-bottom:16px;">
                    Thêm thể loại thành công.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['delete'])): ?>
                <div class="alert alert-success" style="margin-bottom:16px;">
                    Xóa thể loại thành công.
                </div>
            <?php endif; ?>

            <?php if (!empty($_GET['error'])): ?>
                <div class="alert alert-error" style="margin-bottom:16px;">
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>

            <form method="GET" action="index.php" class="filter-bar">
                <input type="hidden" name="page" value="genres">

                <input
                    type="text"
                    name="q"
                    value="<?= htmlspecialchars($q) ?>"
                    placeholder="Tìm thể loại..."
                    maxlength="100"
                    style="padding:8px; border-radius:4px; border:1px solid #444; background:#222; color:#fff;">

                <button type="submit" class="btn-primary">Lọc</button>
                <a href="index.php?page=genres" class="btn-action">Reset</a>
            </form>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:120px;">ID</th>
                            <th>Tên thể loại</th>
                            <th style="text-align:right;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($genres)): ?>
                        <tr>
                            <td colspan="3" style="text-align:center; padding:16px;">
                                Chưa có thể loại
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($genres as $g): ?>
                            <tr>
                                <td>#<?= (int)$g['genre_id'] ?></td>
                                <td><strong><?= htmlspecialchars($g['name']) ?></strong></td>
                                <td style="text-align:right;">
                                    <?php if (hasPermission('genres_delete')): ?>
                                    <form method="POST"
                                          action="index.php?page=genres&action=delete&id=<?= (int)$g['genre_id'] ?>"
                                          style="display:inline;"
                                          onsubmit="return confirm('Xóa thể loại này?');">
                                        <button class="btn-action danger" type="submit">Xóa</button>
                                    </form>
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

    <div id="addGenreModal" class="modal">
        <div class="modal-content" style="max-width:420px;">
            <div class="modal-header">
                <h2>Thêm thể loại mới</h2>
                <button class="btn-close" type="button" onclick="closeModal('addGenreModal')">&times;</button>
            </div>

            <form method="POST"
                  action="index.php?page=genres&action=create"
                  onsubmit="return validateGenreForm(this)">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Tên thể loại <span style="color:red">*</span></label>
                        <input type="text"
                               name="name"
                               required
                               maxlength="100"
                               placeholder="VD: Hành động, Kinh dị..."
                               style="width:100%;padding:10px;border-radius:8px;border:1px solid #444;background:#222;color:#fff;">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-action" onclick="closeModal('addGenreModal')">Hủy</button>
                    <button type="submit" class="btn-primary">Thêm</button>
                </div>
            </form>
        </div>
    </div>

</section>

<script>
function openAddGenreModal() {
    const form = document.querySelector('#addGenreModal form');
    if (form) form.reset();
    openModal('addGenreModal');
}

function validateGenreForm(form) {
    const name = form.querySelector('[name="name"]').value.trim();

    if (name === '') {
        alert('Tên thể loại không được để trống.');
        return false;
    }

    if (name.length > 100) {
        alert('Tên thể loại không được vượt quá 100 ký tự.');
        return false;
    }

    return true;
}
</script>