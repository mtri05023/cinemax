<?php
$shows = $showController->getAllShows();
$halls = $hallController->getAllHalls();
$cinemas = $cinemaController->getAllCinemas();
$movies = $movieService->listMoviesAdmin();
$movies=array_values(array_filter($movies,fn($movie)=>in_array((int)$movie['status'],[1,2],true)));

$error = $_GET['error'] ?? null;
$_SESSION['shows_csrf'] ??= bin2hex(random_bytes(32));
$canDeleteShows=hasPermission('shows_delete') || hasPermission('showtimes_delete');
?>

<!-- Script chuyển data sang js -->
<script>
    window.initialShows = <?= json_encode($shows) ?>;
    window.initialHalls = <?= json_encode($halls) ?>;
    window.initialCinemas = <?= json_encode($cinemas) ?>;
    window.initialMovies = <?= json_encode($movies) ?>;
    window.canDeleteShows = <?= json_encode($canDeleteShows) ?>;
</script>

<section class="schedule-page shows">
    <header class="schedule-heading"><h1>Quản lý lịch chiếu</h1><span class="schedule-total"><?= count($shows) ?> suất chiếu</span></header>
    <?php if ($error): ?><p class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <div id="scheduleOverview" class="dashboard-card schedule-overview">
        <h2>Lịch chiếu tất cả rạp</h2>
        <p class="schedule-description">Tra cứu phim, phòng chiếu và trạng thái mở bán tại tất cả rạp.</p>
        <div class="filter-bar">
            <input type="search" id="overviewSearch" placeholder="Tìm phim, rạp hoặc phòng" aria-label="Tìm lịch chiếu">
            <input type="date" id="overviewDate" aria-label="Lọc theo ngày">
            <button type="button" class="btn-action" onclick="document.getElementById('overviewDate').value='';document.getElementById('overviewSearch').value='';filterScheduleOverview();">Tất cả lịch</button>
        </div>
        <div class="table-responsive">
            <table class="data-table"><thead><tr><th>Phim</th><th>Rạp / phòng</th><th>Ngày</th><th>Giờ</th><th>Trạng thái</th><th></th></tr></thead>
                <tbody id="overviewRows">
                    <?php $overviewShows=$shows; usort($overviewShows,fn($a,$b)=>strcmp($b['show_date'].$b['start_time'],$a['show_date'].$a['start_time'])); ?>
                    <?php foreach ($overviewShows as $show): ?>
                        <?php $activeCinema=false; foreach ($cinemas as $cinema) if ((int)$cinema['CinemaID']===(int)$show['cinema_id'] && (int)$cinema['Status']===1) $activeCinema=true; ?>
                        <tr data-date="<?= htmlspecialchars($show['show_date']) ?>">
                            <td><strong><?= htmlspecialchars($show['movie_title']) ?></strong></td>
                            <td><?= htmlspecialchars($show['cinema_name'].' / '.$show['hall_name']) ?></td>
                            <td><?= date('d/m/Y',strtotime($show['show_date'])) ?></td>
                            <td><?= htmlspecialchars(substr($show['start_time'],0,5).' – '.substr($show['end_time'],0,5)) ?></td>
                            <td><span class="schedule-status status-<?= (int)$show['status']===1?'active':((int)$show['status']===0?'draft':'finished') ?>"><?= match((int)$show['status']) {1=>'Đang mở bán',0=>'Chưa mở bán',default=>'Đã kết thúc'} ?></span></td>
                            <td><div class="schedule-row-actions"><?php if ($activeCinema): ?><button type="button" class="btn-action" onclick="selectTheater(<?= (int)$show['cinema_id'] ?>)">Xem lịch rạp</button><?php else: ?>Rạp đã đóng<?php endif; ?><?php if ($canDeleteShows && (int)$show['status']!==-1): ?><button type="button" class="btn-action danger" onclick="deleteShow(<?= (int)$show['show_id'] ?>)">Xóa lịch chiếu</button><?php endif; ?></div></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p id="overviewEmpty" <?= $shows?'hidden':'' ?>>Chưa có lịch chiếu phù hợp.</p>
        </div>
    </div>

    <!-- ===== CHỌN RẠP ===== -->
    <div id="theaterSelectView" class="theater-select">
        <h2>Tạo lịch Chiếu Phim Vào Rạp</h2>
        <p class="schedule-description">Chọn rạp đang hoạt động để tạo hoặc chỉnh sửa lịch chiếu.</p>
        <div class="theater-list">
            <?php foreach ($cinemas as $cinema): ?>
                <?php if ((int)$cinema['Status']!==1) continue; ?>
                <?php $activeHallCount=count(array_filter($halls,fn($hall)=>(int)$hall['cinema_id']===(int)$cinema['CinemaID'] && (int)$hall['status']===1)); ?>
                <button
                    class="theater-btn"
                    <?= $activeHallCount===0?'disabled':'' ?>
                    onclick="selectTheater('<?= htmlspecialchars($cinema['CinemaID']) ?>')">
                    <span class="cinema-card-top"><span class="schedule-status status-active">Đang hoạt động</span></span>
                    <strong><?= htmlspecialchars($cinema['Name']) ?></strong>
                    <span class="cinema-address"><?= htmlspecialchars($cinema['Address'] ?? '') ?></span>
                    <span class="cinema-card-footer"><span><?= $activeHallCount ?> phòng hoạt động</span><span><?= $activeHallCount>0?'Tạo lịch →':'Cần tạo phòng trước' ?></span></span>
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
                <button type="button" onclick="backToTheater()" class="btn-back">← Tạo lịch Chiếu Phim Vào Rạp</button>
            </div>
            <div class="toolbar-center">
                <button class="nav-btn" onclick="prev()" aria-label="Lịch trước">‹</button>
                <h2 id="currentDate"></h2>
                <button class="nav-btn" onclick="next()" aria-label="Lịch sau">›</button>
            </div>
            <div class="toolbar-right">
                <button class="view-btn active" data-view="day" onclick="setView('day',event)">Ngày</button>
                <button class="view-btn" data-view="week" onclick="setView('week',event)">Tuần</button>
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

        <?php if (!$error && (isset($_GET['add']) || isset($_GET['update']) || isset($_GET['delete']))): ?>
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

        <div class="calendar-date-controls"><label>Đến ngày <input type="date" id="calendarDate" aria-label="Chọn ngày lịch chiếu"></label><button type="button" class="nav-btn" onclick="currentDate=new Date();render();">Hôm nay</button><span>Nhấn ô trống để thêm suất chiếu · Nhấn tên phim để chỉnh sửa</span></div>
        <p class="schedule-description">Cuộn ngang để xem đầy đủ khung giờ hoặc các ngày trong tuần.</p>
        <div id="calendar" tabindex="0" role="region" aria-label="Lịch chiếu rạp, có thể cuộn để xem đầy đủ"></div>
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
            <input type="hidden" name="cinema_id" id="add_cinemaId">

            <label>Phòng</label>
            <!-- Day view: hiển thị tên phòng cố định -->
            <input id="add_hallDisplay" readonly>
            <!-- Week view: cho chọn phòng -->
            <div id="add_hallSelectWrapper" style="display:none; margin-top:8px;">
                <label>Chọn phòng:</label>
                <select id="add_hallSelect" name="hall_id_week" class="form-control"></select>
            </div>

            <label>Phim</label>
            <select id="add_movieSelect" name="movie_id" required>
                <option value="">Chọn phim</option>
                <?php foreach ($movies as $movie): ?>
                    <option value="<?= $movie['movie_id'] ?>"
                        data-duration="<?= $movie['duration_min'] ?>">
                        <?= htmlspecialchars($movie['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Giờ bắt đầu</label>
            <input type="time" id="add_startTime" name="start_time" step="60" required>

            <label>Giờ kết thúc</label>
            <input type="time" id="add_endTime" name="end_time" step="60" readonly>

            <label>Giá vé cơ bản</label>
            <input type="number" id="add_price" name="base_price" min="1" required>

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
            <input type="hidden" name="cinema_id" id="edit_cinemaId">

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
                <?php if ($canDeleteShows): ?><button type="button" class="btn-action danger" id="edit_btnDelete" onclick="deleteShow(document.getElementById('edit_showId').value)">Xóa lịch chiếu</button><?php endif; ?>
                <button type="button" id="edit_btnUpdate" class="btn-save" onclick="submitEdit()">Cập nhật</button>
            </div>
        </div>
    </form>

    <form id="formDeleteShow" method="POST" action="index.php?page=shows&action=delete" hidden>
        <input type="hidden" name="show_id" id="delete_showId">
        <input type="hidden" name="shows_csrf" value="<?= htmlspecialchars($_SESSION['shows_csrf']) ?>">
    </form>
</section>
