<?php
$shows = $showController->getAllShows();
$halls = $hallController->getAllHalls();
$cinemas = $cinemaController->getAllCinemas();
$movies = $movieService->listMoviesAdmin();

$error = $_GET['error'] ?? null;
?>

<!-- Script chuyển data sang js -->
<script>
    window.initialShows = <?= json_encode($shows) ?>;
    window.initialHalls = <?= json_encode($halls) ?>;
    window.initialCinemas = <?= json_encode($cinemas) ?>;
    window.initialMovies = <?= json_encode($movies) ?>;
</script>

<section class="schedule-page shows">

    <!-- ===== CHỌN RẠP ===== -->
    <div id="theaterSelectView" class="theater-select">
        <h2>Chọn rạp chiếu</h2>
        <div class="theater-list">
            <?php foreach ($cinemas as $cinema): ?>
                <button
                    class="theater-btn"
                    onclick="selectTheater('<?= htmlspecialchars($cinema['CinemaID']) ?>')">
                    <?= htmlspecialchars($cinema['Name']) ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ===== CALENDAR VIEW ===== -->
    <div id="calendarView" class="calendar-wrapper">

        <div class="theater-name">
            <span id="theaterName"></span>
        </div>

        <div class="schedule-toolbar">
            <div class="toolbar-left">
                <button onclick="backToTheater()" class="btn-back">← Chọn rạp</button>
            </div>
            <div class="toolbar-center">
                <button class="nav-btn" onclick="prev()">◀</button>
                <h2 id="currentDate"></h2>
                <button class="nav-btn" onclick="next()">▶</button>
            </div>
            <div class="toolbar-right">
                <button class="view-btn active" onclick="setView('day',event)">Day</button>
                <button class="view-btn" onclick="setView('week',event)">Week</button>
            </div>
        </div>

        <div class="schedule-legend">
            <div class="legend-item">
                <span class="legend-color draft"></span> Chưa mở bán
            </div>
            <div class="legend-item">
                <span class="legend-color active"></span> Đang mở bán
            </div>
            <div class="legend-item">
                <span class="legend-color finished"></span> Đã kết thúc
            </div>
        </div>

        <?php if ($error || isset($_GET['add']) || isset($_GET['update']) || isset($_GET['delete'])): ?>
            <div class="alert <?= $error ? 'alert-error' : 'alert-success' ?>" id="autoAlert">
                <?php
                if ($error) {
                    echo htmlspecialchars($error);
                } elseif (isset($_GET['add']) && $_GET['add'] == 1) {
                    echo "Suất chiếu đã được thêm thành công!";
                } elseif (isset($_GET['update']) && $_GET['update'] == 1) {
                    echo "Suất chiếu đã được cập nhật thành công!";
                } elseif (isset($_GET['delete']) && $_GET['delete'] == 1) {
                    echo "Suất chiếu đã được xóa thành công!";
                }
                ?>
            </div>
        <?php endif; ?>

        <div id="calendar"></div>
    </div>


    <!-- ================================================
         FORM THÊM SUẤT CHIẾU (ADD)
    ================================================ -->
    <form id="formAdd" method="POST" action="../admin/index.php?page=shows&action=create">

        <div id="popupAdd" class="popup" style="display:none">
            <h3>Thêm suất chiếu</h3>

            <!-- Ngày + hall được gán qua JS, không cho user sửa -->
            <input type="hidden" name="show_date" id="add_showDate">
            <input type="hidden" name="hall_id" id="add_hallId">

            <label>Phòng</label>
            <!-- Day view: hiển thị tên phòng cố định -->
            <input id="add_hallDisplay" readonly>
            <!-- Week view: cho chọn phòng -->
            <div id="add_hallSelectWrapper" style="display:none; margin-top:8px;">
                <label>Chọn phòng:</label>
                <select id="add_hallSelect" name="hall_id_week" class="form-control"></select>
            </div>

            <label>Phim</label>
            <select id="add_movieSelect" name="movie_id">
                <option value="">Chọn phim</option>
                <?php foreach ($movies as $movie): ?>
                    <option value="<?= $movie['movie_id'] ?>"
                        data-duration="<?= $movie['duration_min'] ?>">
                        <?= htmlspecialchars($movie['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Giờ bắt đầu</label>
            <input type="time" id="add_startTime" name="start_time" step="60">

            <label>Giờ kết thúc</label>
            <input type="time" id="add_endTime" name="end_time" step="60" readonly>

            <label>Giá vé cơ bản</label>
            <input type="number" id="add_price" name="base_price">

            <div class="popup-actions">
                <button type="button" onclick="closeAddPopup()">Huỷ</button>
                <button type="submit" class="btn-save">Lưu</button>
            </div>
        </div>
    </form>


    <!-- ================================================
         FORM SỬA SUẤT CHIẾU (EDIT)
    ================================================ -->
    <form id="formEdit" method="POST" action="">

        <div id="popupEdit" class="popup" style="display:none">
            <h3>Chi tiết suất chiếu</h3>

            <input type="hidden" name="show_id" id="edit_showId">
            <input type="hidden" name="show_date" id="edit_showDate">
            <input type="hidden" name="hall_id" id="edit_hallId">

            <label>Phòng</label>
            <input id="edit_hallDisplay" readonly>

            <label>Phim</label>
            <select id="edit_movieSelect" name="movie_id">
                <option value="">Chọn phim</option>
                <?php foreach ($movies as $movie): ?>
                    <option value="<?= $movie['movie_id'] ?>"
                        data-duration="<?= $movie['duration_min'] ?>">
                        <?= htmlspecialchars($movie['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Giờ bắt đầu</label>
            <input type="time" id="edit_startTime" name="start_time" step="60">

            <label>Giờ kết thúc</label>
            <input type="time" id="edit_endTime" name="end_time" step="60" readonly>

            <label>Giá vé cơ bản</label>
            <input type="number" id="edit_price" name="base_price">

            <label>Trạng thái</label>
            <select id="edit_status" name="status">
                <option value="0">Chưa mở bán</option>
                <option value="1">Đang mở bán</option>
                <option value="-1">Đã kết thúc</option>
            </select>

            <div class="popup-actions">
                <button type="button" onclick="closeEditPopup()">Huỷ</button>
                <button type="button" id="edit_btnUpdate" class="btn-save" onclick="submitEdit()">Cập nhật</button>
            </div>
        </div>
    </form>

</section>