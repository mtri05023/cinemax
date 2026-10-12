<?php
$search   = $_GET['q'] ?? '';
$genreId  = $_GET['genre_id'] ?? null;
$statusTx = $_GET['status_text'] ?? '';

$movies = $movieService->listMoviesAdmin($search, $genreId, $statusTx);
$_SESSION['movies_csrf'] ??= bin2hex(random_bytes(32));
$genres = $movieService->getAllGenres();

$BASE_URL = '/cinemax/cinemax';

function buildPosterSrc($posterUrl, $BASE_URL)
{
    $posterUrl = trim((string)$posterUrl);

    if ($posterUrl === '') return '';

    if (preg_match('/^https?:\/\//i', $posterUrl)) {
        return $posterUrl;
    }

    if (strpos($posterUrl, '/public/') === 0) {
        return $BASE_URL . $posterUrl;
    }

    if ($posterUrl[0] === '/') {
        return $BASE_URL . $posterUrl;
    }

    return $BASE_URL . '/public/' . ltrim($posterUrl, '/');
}
?>

<section class="movies">

    <header class="admin-header">
        <h1>Quản lý phim</h1>
        <?php if (hasPermission('movies_create')): ?>
        <button class="btn-add" onclick="openModal('addMovieModal')">
            <span>Thêm phim mới</span>
        </button>
        <?php endif; ?>
    </header>

    <div class="dashboard-content">
        <div class="dashboard-card">

            <?php if (isset($_GET['add'])): ?>
                <div class="alert alert-success" style="margin-bottom:16px;">Thêm phim thành công.</div>
            <?php endif; ?>

            <?php if (isset($_GET['update'])): ?>
                <div class="alert alert-success" style="margin-bottom:16px;">Cập nhật phim thành công.</div>
            <?php endif; ?>

            <?php if (isset($_GET['delete'])): ?>
                <div class="alert alert-success" style="margin-bottom:16px;">Đã ẩn phim và ngừng bán vé. Lịch sử và doanh thu được giữ nguyên.</div>
            <?php endif; ?>
            <?php if (isset($_GET['restored'])): ?><div class="alert alert-success">Đã kích hoạt lại phim sau khi xác nhận gia hạn bản quyền.</div><?php endif; ?>

            <?php if (!empty($_GET['error'])): ?>
                <div class="alert alert-error" style="margin-bottom:16px;">
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>

            <form method="GET" action="index.php" class="filter-bar">
                <input type="hidden" name="page" value="movies">

                <input
                    type="text"
                    name="q"
                    value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                    placeholder="Tìm tên phim..."
                    style="padding:8px; border-radius:4px; border:1px solid #444; background:#222; color:#fff;">

                <select
                    name="genre_id"
                    style="padding:8px; border-radius:4px; border:1px solid #444; background:#222; color:#fff;">
                    <option value="">Tất cả thể loại</option>
                    <?php foreach ($genres as $g): ?>
                        <option value="<?= (int)$g['genre_id'] ?>"
                            <?= ((string)$g['genre_id'] === (string)($_GET['genre_id'] ?? '')) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($g['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php $st = $_GET['status_text'] ?? ''; ?>
                <select
                    name="status_text"
                    style="padding:8px; border-radius:4px; border:1px solid #444; background:#222; color:#fff;">
                    <option value="" <?= $st === '' ? 'selected' : '' ?>>Tất cả trạng thái</option>
                <option value="Sắp chiếu" <?= $st === 'Sắp chiếu' ? 'selected' : '' ?>>Sắp chiếu</option>
<option value="Đang chiếu" <?= $st === 'Đang chiếu' ? 'selected' : '' ?>>Đang chiếu</option>
<option value="Ngừng chiếu" <?= $st === 'Ngừng chiếu' ? 'selected' : '' ?>>Ngừng chiếu</option>
<option value="Đã ẩn" <?= $st === 'Đã ẩn' ? 'selected' : '' ?>>Đã ẩn / hết bản quyền</option>
                </select>

                <button type="submit" class="btn-primary">Lọc</button>
                <a href="index.php?page=movies" class="btn-action">Reset</a>
            </form>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Poster</th>
                            <th>Tên phim</th>
                            <th>Thể loại</th>
                            <th>Thời lượng</th>
                            <th>Ngày chiếu</th>
                            <th>Trạng thái</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($movies)): ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding:16px;">
                                Chưa có dữ liệu phim
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($movies as $m): ?>
                            <?php
                                $posterSrc = buildPosterSrc($m['poster_url'] ?? '', $BASE_URL);
                                $badge = 'badge-warning';
                                $txt = 'Sắp chiếu';

                                if ((int)$m['status'] === 2) {
                                    $badge = 'badge-success';
                                    $txt = 'Đang chiếu';
                                }

                                if ((int)$m['status'] === 3) {
                                    $badge = 'badge-danger';
                                    $txt = 'Ngừng chiếu';
                                }
                                if ((int)$m['status'] === 0) { $badge='badge-danger'; $txt='Đã ẩn / ngừng khai thác'; }

                                $movieGenreIds = [];
                                if (!empty($m['genre_ids'])) {
                                    $movieGenreIds = explode(',', $m['genre_ids']);
                                }
                            ?>
                            <tr>
                                <td>#<?= (int)$m['movie_id'] ?></td>
                                <td>
                                    <?php if ($posterSrc !== ''): ?>
                                        <img
                                            src="<?= htmlspecialchars($posterSrc) ?>"
                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';"
                                            style="width:50px;height:75px;object-fit:cover;border-radius:4px;">
                                        <span style="display:none;color:#888;">Chưa có poster</span>
                                    <?php else: ?>
                                        <span style="color:#888;">Chưa có poster</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= htmlspecialchars($m['title']) ?></strong></td>
                                <td><?= htmlspecialchars($m['genre_names'] ?? '') ?></td>
                                <td><?= (int)$m['duration_min'] ?>p</td>
                                <td><?= $m['release_date'] ? date('d/m/Y', strtotime($m['release_date'])) : '' ?></td>
                                <td>
                                    <span class="badge <?= $badge ?>"><?= $txt ?></span>
                                </td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn-action"
                                        onclick='openEditMovieModal(
                                            <?= (int)$m["movie_id"] ?>,
                                            <?= json_encode($m["title"] ?? "") ?>,
                                            <?= json_encode($m["description"] ?? "") ?>,
                                            <?= json_encode($m["duration_min"] ?? "") ?>,
                                            <?= json_encode($m["release_date"] ?? "") ?>,
                                            <?= json_encode($m["trailer_url"] ?? "") ?>,
                                            <?= json_encode($m["status"] ?? "") ?>,
                                            <?= json_encode($m["director"] ?? "") ?>,
                                            <?= json_encode($m["actors"] ?? $m["cast"] ?? "") ?>,
                                            <?= json_encode($m["poster_url"] ?? "") ?>,
                                            <?= json_encode($movieGenreIds) ?>
                                        )'>
                                        Sửa
                                    </button>

                                    <?php if ((int)$m['status']===0): ?>
                                    <form method="POST" action="index.php?page=movies&action=restore&id=<?= (int)$m['movie_id'] ?>" style="display:inline;" onsubmit="return confirm('Bạn xác nhận đã mua hoặc gia hạn bản quyền phim này và muốn kích hoạt lại?');">
                                        <input type="hidden" name="movies_csrf" value="<?= htmlspecialchars($_SESSION['movies_csrf']) ?>">
                                        <input type="hidden" name="license_renewed" value="1">
                                        <button type="submit" class="btn-action">Kích hoạt sau gia hạn</button>
                                    </form>
                                    <?php else: ?>
                                    <form method="POST"
                                          action="index.php?page=movies&action=delete&id=<?= (int)$m['movie_id'] ?>"
                                          style="display:inline;">
                                        <input type="hidden" name="movies_csrf" value="<?= htmlspecialchars($_SESSION['movies_csrf']) ?>">
                                        <button type="submit"
                                                class="btn-action danger"
                                                onclick="return confirm('Ẩn phim và ngừng bán vé? Lịch sử và doanh thu của phim sẽ được giữ nguyên.')">
                                            Ẩn phim
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <?php  ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <div id="addMovieModal" class="modal movie-modal">
        <div class="modal-content movie-modal-content">
            <div class="modal-header">
                <h2>Thêm phim mới</h2>
                <button type="button" class="btn-close" onclick="closeModal('addMovieModal')">&times;</button>
            </div>

            <form method="POST"
                  action="index.php?page=movies&action=create"
                  enctype="multipart/form-data"
                  onsubmit="return validateMovieForm(this, false)">
                <div class="modal-body">

                    <div class="movie-form-grid">
                        <div>
                            <div class="form-group">
                                <label>Tên phim <span style="color:red">*</span></label>
                                <input type="text" name="title" required maxlength="255">
                            </div>

                            <div class="form-group">
                                <label>Đạo diễn <span style="color:red">*</span></label>
                                <input type="text" name="director" required placeholder="Nhập tên đạo diễn">
                            </div>

                            <div class="form-group">
                                <label>Tên diễn viên <span style="color:red">*</span></label>
                                <input type="text" name="actors" required placeholder="VD: Trấn Thành, Sam Worthington">
                            </div>

                            <div class="form-group">
                                <label>Thể loại <span style="color:red">*</span></label>
                                <div class="genre-grid" style="max-height:160px;overflow:auto;border:1px solid #444;padding:10px;border-radius:8px;">
                                    <?php foreach ($genres as $g): ?>
                                        <label class="checkbox-item" style="display:inline-block;min-width:140px;margin-bottom:8px;">
                                            <input type="checkbox" name="genre_ids[]" value="<?= (int)$g['genre_id'] ?>">
                                            <?= htmlspecialchars($g['name']) ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="form-group">
                                <label>Poster <span style="color:red">*</span></label>

                                <div class="movie-poster-field">
                                    <img
                                        id="addPosterPreview"
                                        src=""
                                        alt="Preview Poster"
                                        onerror="this.style.display='none';"
                                        style="display:none;width:90px;height:130px;object-fit:cover;border-radius:8px;border:1px solid #444;">

                                    <div class="movie-poster-control">
                                        <input
                                            type="file"
                                            name="poster"
                                            accept="image/*"
                                            required
                                            onchange="previewPoster(this, 'addPosterPreview')">

                                        <small style="display:block;margin-top:8px;color:#aaa;">
                                            Bắt buộc chọn file jpg, jpeg, png, webp
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Link Trailer</label>
                                <input type="url" name="trailer_url" placeholder="https://youtube.com/...">
                            </div>

                            <div class="form-group">
                                <label>Mô tả</label>
                                <textarea name="description" rows="5"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="movie-form-row">
                        <div class="form-group">
                            <label>Thời lượng (phút) <span style="color:red">*</span></label>
                            <input type="number" name="duration_min" min="1" max="500" required>
                        </div>

                        <div class="form-group">
                            <label>Trạng thái <span style="color:red">*</span></label>
                            <select name="status" required>
                            <option value="1">Sắp chiếu</option>
                            <option value="2">Đang chiếu</option>
                            <option value="3">Ngừng chiếu</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Ngày chiếu <span style="color:red">*</span></label>
                            <input type="date"
                                   name="release_date"
                                   required
                                   min="1900-01-01"
                                   max="2100-12-31"
                                   onclick="this.showPicker && this.showPicker()">
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-action" onclick="closeModal('addMovieModal')">Hủy</button>
                    <button type="submit" class="btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editMovieModal" class="modal movie-modal">
        <div class="modal-content movie-modal-content">
            <div class="modal-header">
                <h2>Sửa phim</h2>
                <button type="button" class="btn-close" onclick="closeModal('editMovieModal')">&times;</button>
            </div>

            <form method="POST"
                  action="index.php?page=movies&action=update"
                  enctype="multipart/form-data"
                  onsubmit="return validateMovieForm(this, true)">
                <input type="hidden" name="movie_id" id="edit_movie_id">
                <input type="hidden" name="existing_poster_url" id="edit_existing_poster_url">

                <div class="modal-body">

                    <div class="movie-form-grid">
                        <div>
                            <div class="form-group">
                                <label>Tên phim <span style="color:red">*</span></label>
                                <input type="text" name="title" id="edit_title" required maxlength="255">
                            </div>

                            <div class="form-group">
                                <label>Đạo diễn <span style="color:red">*</span></label>
                                <input type="text" name="director" id="edit_director" required>
                            </div>

                            <div class="form-group">
                                <label>Tên diễn viên <span style="color:red">*</span></label>
                                <input type="text" name="actors" id="edit_actors" required>
                            </div>

                            <div class="form-group">
                                <label>Thể loại <span style="color:red">*</span></label>
                                <div class="genre-grid" style="max-height:160px;overflow:auto;border:1px solid #444;padding:10px;border-radius:8px;">
                                    <?php foreach ($genres as $g): ?>
                                        <label class="checkbox-item" style="display:inline-block;min-width:140px;margin-bottom:8px;">
                                            <input type="checkbox" class="edit_genre_checkbox" name="genre_ids[]" value="<?= (int)$g['genre_id'] ?>">
                                            <?= htmlspecialchars($g['name']) ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="form-group">
                                <label>Đổi poster</label>

                                <div class="movie-poster-field">
                                    <img
                                        id="editPosterPreview"
                                        src=""
                                        alt="Preview Poster"
                                        onerror="this.style.display='none';"
                                        style="display:none;width:90px;height:130px;object-fit:cover;border-radius:8px;border:1px solid #444;">

                                    <div class="movie-poster-control">
                                        <input
                                            type="file"
                                            name="poster"
                                            accept="image/*"
                                            onchange="previewPoster(this, 'editPosterPreview')">

                                        <small style="display:block;margin-top:8px;color:#aaa;">
                                            Không chọn ảnh mới thì giữ poster cũ
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Link Trailer</label>
                                <input type="url" name="trailer_url" id="edit_trailer_url" placeholder="https://youtube.com/...">
                            </div>

                            <div class="form-group">
                                <label>Mô tả</label>
                                <textarea name="description" id="edit_description" rows="5"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="movie-form-row">
                        <div class="form-group">
                            <label>Thời lượng (phút) <span style="color:red">*</span></label>
                            <input type="number" name="duration_min" id="edit_duration_min" min="1" max="500" required>
                        </div>

                        <div class="form-group">
                            <label>Trạng thái <span style="color:red">*</span></label>
                            <select name="status" id="edit_status" required>
                                <option value="0">Đã ẩn / ngừng khai thác</option>
                               <option value="1">Sắp chiếu</option>
                                <option value="2">Đang chiếu</option>
                                <option value="3">Ngừng chiếu</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Ngày chiếu <span style="color:red">*</span></label>
                            <input type="date"
                                   name="release_date"
                                   id="edit_release_date"
                                   required
                                   min="1900-01-01"
                                   max="2100-12-31"
                                   onclick="this.showPicker && this.showPicker()">
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-action" onclick="closeModal('editMovieModal')">Hủy</button>
                    <button type="submit" class="btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>

</section>

<script>
function previewPoster(input, previewId) {
    const preview = document.getElementById(previewId);

    if (!input.files || !input.files[0]) {
        preview.src = '';
        preview.style.display = 'none';
        return;
    }

    const file = input.files[0];

    if (!file.type.startsWith('image/')) {
        alert('Vui lòng chọn file ảnh hợp lệ.');
        input.value = '';
        preview.src = '';
        preview.style.display = 'none';
        return;
    }

    const reader = new FileReader();
    reader.onload = function (e) {
        preview.src = e.target.result;
        preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
}

function openAddMovieModal() {
    const form = document.querySelector('#addMovieModal form');
    if (form) form.reset();

    const addPreview = document.getElementById('addPosterPreview');
    addPreview.src = '';
    addPreview.style.display = 'none';

    const genreCheckboxes = document.querySelectorAll('#addMovieModal input[name="genre_ids[]"]');
    genreCheckboxes.forEach(cb => cb.checked = false);

    openModal('addMovieModal');
}

function openEditMovieModal(id, title, description, durationMin, releaseDate, trailerUrl, status, director, actors, posterUrl, genreIds) {
    document.getElementById('edit_movie_id').value = id;
    document.getElementById('edit_title').value = title ?? '';
    document.getElementById('edit_description').value = description ?? '';
    document.getElementById('edit_duration_min').value = durationMin ?? '';
    document.getElementById('edit_director').value = director ?? '';
    document.getElementById('edit_actors').value = actors ?? '';
    document.getElementById('edit_trailer_url').value = trailerUrl ?? '';
    document.getElementById('edit_status').value = status ?? '';
    document.getElementById('edit_existing_poster_url').value = posterUrl ?? '';

    let normalizedDate = '';
    if (releaseDate && typeof releaseDate === 'string') {
        if (/^\d{4}-\d{2}-\d{2}$/.test(releaseDate)) {
            normalizedDate = releaseDate;
        } else {
            const d = new Date(releaseDate);
            if (!isNaN(d.getTime())) {
                const yyyy = d.getFullYear();
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const dd = String(d.getDate()).padStart(2, '0');
                normalizedDate = `${yyyy}-${mm}-${dd}`;
            }
        }
    }
    document.getElementById('edit_release_date').value = normalizedDate;

    const editPreview = document.getElementById('editPosterPreview');
    let previewSrc = '';

    if (posterUrl && String(posterUrl).trim() !== '') {
        if (
            posterUrl.startsWith('http://') ||
            posterUrl.startsWith('https://') ||
            posterUrl.startsWith('/')
        ) {
            previewSrc = buildPosterJsPath(posterUrl);
        } else {
            previewSrc = buildPosterJsPath(posterUrl);
        }
    }

    if (previewSrc !== '') {
        editPreview.src = previewSrc;
        editPreview.style.display = 'block';
    } else {
        editPreview.src = '';
        editPreview.style.display = 'none';
    }

    const checkboxes = document.querySelectorAll('.edit_genre_checkbox');
    const normalizedGenres = Array.isArray(genreIds) ? genreIds.map(String) : [];

    checkboxes.forEach(cb => {
        cb.checked = normalizedGenres.includes(String(cb.value));
    });

    openModal('editMovieModal');
}

function buildPosterJsPath(posterUrl) {
    if (!posterUrl) return '';

    if (/^https?:\/\//i.test(posterUrl)) return posterUrl;

    if (posterUrl.startsWith('/public/')) {
        return '<?= $BASE_URL ?>' + posterUrl;
    }

    if (posterUrl.startsWith('/')) {
        return '<?= $BASE_URL ?>' + posterUrl;
    }

    return '<?= $BASE_URL ?>/public/' + posterUrl.replace(/^\/+/, '');
}

function validateMovieForm(form, isEdit = false) {
    const title = form.querySelector('[name="title"]').value.trim();
    const director = form.querySelector('[name="director"]').value.trim();
    const actors = form.querySelector('[name="actors"]').value.trim();
    const duration = form.querySelector('[name="duration_min"]').value;
    const releaseDate = form.querySelector('[name="release_date"]').value;
    const trailerUrlInput = form.querySelector('[name="trailer_url"]');
    const trailerUrl = trailerUrlInput ? trailerUrlInput.value.trim() : '';
    const statusInput = form.querySelector('[name="status"]');
    const status = statusInput ? statusInput.value : '';
    const genreCheckboxes = form.querySelectorAll('[name="genre_ids[]"]:checked');
    const posterInput = form.querySelector('[name="poster"]');

    if (title === '') {
        alert('Tên phim không được để trống.');
        return false;
    }

    if (title.length > 255) {
        alert('Tên phim không được vượt quá 255 ký tự.');
        return false;
    }

    if (director === '') {
        alert('Vui lòng nhập tên đạo diễn.');
        return false;
    }

    if (director.length > 255) {
        alert('Tên đạo diễn quá dài.');
        return false;
    }

    if (actors === '') {
        alert('Vui lòng nhập tên diễn viên.');
        return false;
    }

    if (!duration || isNaN(duration) || parseInt(duration) <= 0) {
        alert('Thời lượng phim không hợp lệ.');
        return false;
    }

    if (!status || !['0', '1', '2', '3'].includes(status)) {
    alert('Vui lòng chọn trạng thái phim.');
    return false;
}

    if (genreCheckboxes.length === 0) {
        alert('Vui lòng chọn ít nhất 1 thể loại.');
        return false;
    }

    if (!releaseDate) {
        alert('Vui lòng chọn ngày chiếu.');
        return false;
    }

    const minDate = '1900-01-01';
    const maxDate = '2100-12-31';

    if (releaseDate < minDate || releaseDate > maxDate) {
        alert('Ngày chiếu phải nằm trong khoảng từ 1900-01-01 đến 2100-12-31.');
        return false;
    }

    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    const todayStr = `${yyyy}-${mm}-${dd}`;

   if (status === '1' && releaseDate <= todayStr) {
    alert('Phim "Sắp chiếu" phải có ngày chiếu sau hôm nay.');
    return false;
}

if (status === '2' && releaseDate > todayStr) {
    alert('Phim "Đang chiếu" không thể có ngày chiếu ở tương lai.');
    return false;
}

    if (trailerUrl !== '') {
        try {
            new URL(trailerUrl);
        } catch (e) {
            alert('Link trailer không đúng định dạng URL.');
            return false;
        }
    }

    if (!isEdit) {
        if (!posterInput || !posterInput.files || !posterInput.files[0]) {
            alert('Vui lòng tải poster cho phim.');
            return false;
        }
    }

    return true;
}
</script>
