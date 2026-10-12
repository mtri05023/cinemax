/**
 * JavaScript cho trang quản lý Rạp chiếu
 */

let cinemasData = [];
let locationsData = [];
let statusesData = [];

// Load dữ liệu khi trang được tải
document.addEventListener('DOMContentLoaded', async function() {
    try {
        await loadAllData();
        renderCinemasTable();
        populateLocationSelect();
        populateStatusSelect();
    } catch (error) {
        console.error('Lỗi khi load dữ liệu:', error);
        showAlert('Có lỗi xảy ra khi tải dữ liệu: ' + error.message, 'error');
    }
});

/**
 * Load tất cả dữ liệu cần thiết
 */
async function loadAllData() {
    try {
        [cinemasData, locationsData, statusesData] = await Promise.all([
            getAllCinemas(),
            getAllLocations(),
            getAllCinemaStatuses()
        ]);
    } catch (error) {
        throw new Error('Không thể tải dữ liệu: ' + error.message);
    }
}

/**
 * Render bảng rạp chiếu
 */
function renderCinemasTable() {
    const tbody = document.getElementById('theatersTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (cinemasData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 20px;">Chưa có dữ liệu</td></tr>';
        return;
    }
    
    cinemasData.forEach(cinema => {
        const isActive = String(cinema.Status) === '1';
        const statusLabel = isActive ? 'Đang hoạt động' : 'Ngừng hoạt động';
        const actionButton = isActive
            ? `<button class="btn-action danger" onclick="closeCinema(${cinema.CinemaID})">Đóng rạp</button>`
            : `<button class="btn-action" onclick="openCinema(${cinema.CinemaID})">Mở rạp</button>`;

        const row = document.createElement('tr');
        if (!isActive) {
            row.style.opacity = '0.45';
            row.style.transition = 'opacity 0.2s ease';
        }
        row.innerHTML = `
            <td><strong>#${cinema.CinemaID}</strong></td>
            <td>${cinema.Name}</td>
            <td>${cinema.Address}</td>
            <td>${cinema.LocationName || 'N/A'}</td>
            <td>
                ${cinema.HallCount || 0} phòng<br>
                <small style="color:${isActive ? '#4caf50' : '#e53935'};">${statusLabel}</small>
            </td>
            <td>
                <a href="#" class="btn-action" onclick="editCinema(${cinema.CinemaID}); return false;">Sửa</a>
                ${actionButton}
                <a href="index.php?page=halls&cinema_id=${cinema.CinemaID}" class="btn-action">Quản lý phòng</a>
            </td>
        `;
        tbody.appendChild(row);
    });
}

/**
 * Populate location select trong modal
 */
function populateLocationSelect() {
    const selects = document.querySelectorAll('#addTheaterModal select[name="location_id"], #editCinemaModal select[name="location_id"]');
    selects.forEach(select => {
        if (!select) return;
        
        select.innerHTML = '<option value="">-- Chọn thành phố --</option>';
        locationsData.forEach(location => {
            const option = document.createElement('option');
            option.value = location.LocationID;
            option.textContent = location.Name;
            select.appendChild(option);
        });
    });
}

/**
 * Populate status select trong modal
 */
function populateStatusSelect() {
    const selects = document.querySelectorAll('#addTheaterModal select[name="status_id"], #editCinemaModal select[name="status_id"]');
    selects.forEach(select => {
        if (!select) return;
        
        select.innerHTML = '<option value="">-- Chọn trạng thái --</option>';
        statusesData.forEach(status => {
            const option = document.createElement('option');
            option.value = status.StatusID;
            option.textContent = status.StatusName;
            select.appendChild(option);
        });
    });
}

/**
 * Edit cinema
 */
async function editCinema(cinemaId) {
    try {
        const cinema = await getCinemaById(cinemaId);
        if (!cinema) {
            showAlert('Không tìm thấy rạp chiếu', 'error');
            return;
        }
        
        openEditModal(cinema);
    } catch (error) {
        showAlert('Lỗi khi lấy thông tin rạp chiếu: ' + error.message, 'error');
    }
}

/**
 * Đóng rạp (ngừng hoạt động)
 */
async function closeCinema(cinemaId) {
    if (!confirm('Bạn có chắc chắn muốn đóng rạp này (ngừng hoạt động)?')) {
        return;
    }

    try {
        // Hàm deleteCinema() được khai báo ở public/assets/js/api.js
        await window.deleteCinema(cinemaId);
        showAlert('Đóng rạp thành công (đã chuyển sang ngừng hoạt động)!', 'success');
        await loadAllData();
        renderCinemasTable();
    } catch (error) {
        showAlert('Lỗi khi đóng rạp: ' + error.message, 'error');
    }
}

/**
 * Mở rạp (chuyển về trạng thái đang hoạt động)
 */
async function openCinema(cinemaId) {
    const cinema = cinemasData.find(c => String(c.CinemaID) === String(cinemaId));
    if (!cinema) {
        showAlert('Không tìm thấy rạp để mở lại', 'error');
        return;
    }

    if (!confirm('Mở lại rạp này (chuyển sang đang hoạt động)?')) {
        return;
    }

    const data = {
        name: cinema.Name,
        address: cinema.Address,
        location_id: cinema.LocationID,
        status_id: 1
    };

    try {
        await updateCinema(cinemaId, data);
        showAlert('Mở rạp thành công!', 'success');
        await loadAllData();
        renderCinemasTable();
    } catch (error) {
        showAlert('Lỗi khi mở rạp: ' + error.message, 'error');
    }
}

/**
 * Open edit modal
 */
function openEditModal(cinema) {
    // Tạo modal nếu chưa có
    let modal = document.getElementById('editCinemaModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'editCinemaModal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Cập nhật rạp chiếu</h2>
                    <button class="btn-close" onclick="closeModal('editCinemaModal')">&times;</button>
                </div>
                <form id="editCinemaForm" onsubmit="handleUpdateCinema(event); return false;">
                    <input type="hidden" name="cinema_id" id="editCinemaId">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Tên rạp</label>
                            <input type="text" name="name" id="editCinemaName" required>
                        </div>
                        <div class="form-group">
                            <label>Địa chỉ</label>
                            <input type="text" name="address" id="editCinemaAddress" required>
                        </div>
                        <div class="form-group">
                            <label>Thành phố</label>
                            <select name="location_id" id="editLocationId" required></select>
                        </div>
                        <div class="form-group">
                            <label>Trạng thái</label>
                            <select name="status_id" id="editStatusId" required></select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-action" onclick="closeModal('editCinemaModal')">Hủy</button>
                        <button type="submit" class="btn-primary">Cập nhật</button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);
        
        // Populate selects ngay sau khi modal được tạo
        populateLocationSelect();
        populateStatusSelect();
        
        // Điền dữ liệu sau khi selects đã được populate
        setTimeout(() => {
            const editCinemaId = document.getElementById('editCinemaId');
            const editCinemaName = document.getElementById('editCinemaName');
            const editCinemaAddress = document.getElementById('editCinemaAddress');
            const locationSelect = document.getElementById('editLocationId');
            const statusSelect = document.getElementById('editStatusId');
            
            if (editCinemaId) editCinemaId.value = cinema.CinemaID;
            if (editCinemaName) editCinemaName.value = cinema.Name;
            if (editCinemaAddress) editCinemaAddress.value = cinema.Address;
            
            if (locationSelect) {
                locationSelect.value = cinema.LocationID;
            }
            if (statusSelect) {
                statusSelect.value = cinema.StatusID;
            }
        }, 100);
    } else {
        // Modal đã tồn tại, chỉ cần populate và điền dữ liệu
        populateLocationSelect();
        populateStatusSelect();
        
        setTimeout(() => {
            const editCinemaId = document.getElementById('editCinemaId');
            const editCinemaName = document.getElementById('editCinemaName');
            const editCinemaAddress = document.getElementById('editCinemaAddress');
            const locationSelect = document.getElementById('editLocationId');
            const statusSelect = document.getElementById('editStatusId');
            
            if (editCinemaId) editCinemaId.value = cinema.CinemaID;
            if (editCinemaName) editCinemaName.value = cinema.Name;
            if (editCinemaAddress) editCinemaAddress.value = cinema.Address;
            
            if (locationSelect) {
                locationSelect.value = cinema.LocationID;
            }
            if (statusSelect) {
                statusSelect.value = cinema.StatusID;
            }
        }, 100);
    }
    
    // Mở modal
    modal.classList.add('active');
}

/**
 * Handle update cinema
 */
async function handleUpdateCinema(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const cinemaId = formData.get('cinema_id');
    
    if (!cinemaId) {
        showAlert('Lỗi: Không tìm thấy ID rạp chiếu', 'error');
        return;
    }
    
    const name = formData.get('name');
    const address = formData.get('address');
    const locationId = formData.get('location_id');
    const statusId = formData.get('status_id');
    
    if (!name || !address || !locationId || !statusId) {
        showAlert('Vui lòng điền đầy đủ thông tin', 'error');
        return;
    }
    
    const data = {
        name: name,
        address: address,
        location_id: locationId,
        status_id: statusId
    };
    
    try {
        await updateCinema(cinemaId, data);
        showAlert('Cập nhật rạp chiếu thành công!', 'success');
        closeModal('editCinemaModal');
        await loadAllData();
        renderCinemasTable();
    } catch (error) {
        console.error('Update cinema error:', error);
        showAlert('Lỗi khi cập nhật: ' + error.message, 'error');
    }
}

/**
 * Handle create cinema
 */
async function handleCreateCinema(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    const data = {
        name: formData.get('name'),
        address: formData.get('address'),
        location_id: formData.get('location_id'),
        status_id: formData.get('status_id')
    };
    
    try {
        await createCinema(data);
        showAlert('Thêm rạp chiếu thành công!', 'success');
        closeModal('addTheaterModal');
        form.reset();
        await loadAllData();
        renderCinemasTable();
    } catch (error) {
        showAlert('Lỗi khi thêm rạp chiếu: ' + error.message, 'error');
    }
}

/**
 * Show alert
 */
function showAlert(message, type = 'success') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    
    const content = document.querySelector('.theaters .dashboard-content');
    if (content) {
        content.insertBefore(alert, content.firstChild);
        
        setTimeout(() => {
            alert.remove();
        }, 3000);
    }
}
// Override form submit cho modal thêm mới
document.addEventListener('DOMContentLoaded', function() {
    const addForm = document.querySelector('#addTheaterModal form');
    if (addForm) {
        addForm.addEventListener('submit', handleCreateCinema);
    }
});

