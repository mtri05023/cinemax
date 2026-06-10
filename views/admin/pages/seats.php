<section class="seats">

    <header class="admin-header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="index.php?page=halls" class="btn-action">← Quay lại</a>
            <h1>Sơ đồ ghế: Đang tải...</h1>
        </div>
        <div style="color: #888;" id="hallInfo">Đang tải thông tin...</div>

        <div style="display: flex; gap: 10px;">
            <button class="btn-action" onclick="openCreateSeatModal()" style="background: #46d369; color: white;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Thêm ghế
            </button>
            <button class="btn-action" onclick="openAutoCreateModal()" style="background: #2196F3; color: white;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="9" y1="3" x2="9" y2="21"></line>
                </svg>
                Tạo sơ đồ tự động
            </button>
            <button class="btn-action danger" onclick="resetSeatLayout()">
                Xóa sơ đồ & Làm lại
            </button>
        </div>
    </header>

    <div class="dashboard-content">

        <!-- Alert mẫu -->
        <!-- <div class="alert alert-success">Thành công!</div> -->
        <!-- <div class="alert alert-error">Có lỗi xảy ra</div> -->

        <div class="legend" id="seatLegend">
            <div class="legend-item" style="margin-left: 15px; border-left: 1px solid #444; padding-left: 15px;">
                👉 Click ghế để đổi loại | ❌ Click dấu X để xóa
            </div>
        </div>

        <div class="editor-area">
            <div class="screen-line"></div>
            <div style="margin-bottom: 30px; font-size: 12px; color: #666; letter-spacing: 2px;">
                MÀN HÌNH
            </div>

            <div class="seat-grid">
                <div style="text-align: center; padding: 40px; color: #666;">
                    <div class="loading">Đang tải sơ đồ ghế...</div>
                </div>
            </div>
        </div>

    </div>
</section>

<script src="../../public/assets/js/api.js"></script>
<script src="../../public/assets/js/seats-admin.js"></script>