/**
 * JavaScript cho trang quản lý Phòng chiếu
 * Lưu ý: Phần render bảng & filter đã chuyển sang PHP.
 * JS chỉ xử lý CRUD (thêm / sửa / xóa) và modal.
 */

// Dữ liệu hỗ trợ cho edit modal (lấy theo API khi cần)
let cinemasData = [];
let statusesData = [];

// Load dữ liệu hỗ trợ (danh sách rạp & trạng thái) cho modal khi trang sẵn sàng
document.addEventListener('DOMContentLoaded', async function() {
    try {
        [cinemasData, statusesData] = await Promise.all([
            getAllCinemas(),
            getAllHallStatuses()
        ]);

        // Nếu API trạng thái trả về rỗng (chưa cấu hình trong DB),
        // dùng danh sách mặc định giống PHP fallback ở halls.php
        if (!statusesData || !statusesData.length) {
            statusesData = [
                { StatusID: 1, StatusName: 'Đang hoạt động' },
                { StatusID: 0, StatusName: 'Tạm dừng' },
                { StatusID: 2, StatusName: 'Bảo trì' }
            ];
        }
    } catch (error) {
        console.error('Lỗi khi load dữ liệu hỗ trợ:', error);
        // Fallback mặc định khi lỗi API
        cinemasData = [];
        statusesData = [
            { StatusID: 1, StatusName: 'Đang hoạt động' },
            { StatusID: 0, StatusName: 'Tạm dừng' },
            { StatusID: 2, StatusName: 'Bảo trì' }
        ];
    }

    // Gắn handler cho form thêm mới
    const addForm = document.querySelector('#addScreenModal form');
    if (addForm) {
        addForm.addEventListener('submit', handleCreateHall);
    }
});

/**
 * Edit hall
 */
async function editHall(hallId) {
    try {
        const hall = await getHallById(hallId);
        if (!hall) {
            showAlert('Không tìm thấy phòng chiếu', 'error');
            return;
        }
        
        // Tạo hoặc mở modal edit
        openEditModal(hall);
    } catch (error) {
        showAlert('Lỗi khi lấy thông tin phòng chiếu: ' + error.message, 'error');
    }
}

/**
 * Delete hall (handler function)
 */
async function deleteHallHandler(hallId) {
    if (!confirm('CẢNH BÁO: Xóa phòng sẽ xóa luôn sơ đồ ghế của phòng này?')) {
        return;
    }
    
    try {
        await deleteHall(hallId);
        showAlert('Xóa phòng chiếu thành công!', 'success');
        // Reload lại trang để cập nhật bảng render bằng PHP
        setTimeout(() => window.location.reload(), 500);
    } catch (error) {
        showAlert('Lỗi khi xóa phòng chiếu: ' + error.message, 'error');
    }
}

/**
 * Show alert
 */
function showAlert(message, type = 'success') {
    // Tạo alert element
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    
    // Thêm vào dashboard-content
    const content = document.querySelector('.halls .dashboard-content');
    if (content) {
        content.insertBefore(alert, content.firstChild);
        
        // Tự động xóa sau 3 giây
        setTimeout(() => {
            alert.remove();
        }, 3000);
    }
}

/**
 * Open edit modal
 */
function openEditModal(hall) {
    // Tạo modal nếu chưa có
    let modal = document.getElementById('editHallModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'editHallModal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Cập nhật phòng chiếu</h2>
                    <button class="btn-close" onclick="closeModal('editHallModal')">&times;</button>
                </div>
                <form id="editHallForm" onsubmit="handleUpdateHall(event); return false;">
                    <input type="hidden" name="hall_id" id="editHallId">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Chọn Rạp</label>
                            <select name="cinema_id" id="editCinemaId" required></select>
                        </div>
                        <div class="form-group">
                            <label>Tên phòng</label>
                            <input type="text" name="name" id="editHallName" placeholder="VD: Phòng 1, Phòng IMAX" required>
                        </div>
                        <div class="form-group">
                            <label>Trạng thái</label>
                            <select name="status_id" id="editStatusId" required></select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-action" onclick="closeModal('editHallModal')">Hủy</button>
                        <button type="submit" class="btn-primary">Cập nhật</button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);
        
        // Tạo option cho selects từ dữ liệu đã load
        const editCinemaSelect = modal.querySelector('#editCinemaId');
        const editStatusSelect = modal.querySelector('#editStatusId');
        if (editCinemaSelect && cinemasData.length) {
            editCinemaSelect.innerHTML = '<option value="">-- Chọn rạp --</option>';
            cinemasData.forEach(cinema => {
                const opt = document.createElement('option');
                opt.value = cinema.CinemaID;
                opt.textContent = cinema.Name;
                editCinemaSelect.appendChild(opt);
            });
        }
        if (editStatusSelect && statusesData.length) {
            editStatusSelect.innerHTML = '<option value="">-- Chọn trạng thái --</option>';
            statusesData.forEach(status => {
                const opt = document.createElement('option');
                opt.value = status.StatusID;
                opt.textContent = status.StatusName;
                editStatusSelect.appendChild(opt);
            });
        }

        // Điền dữ liệu sau khi selects đã được populate
        setTimeout(() => {
            const editHallId = document.getElementById('editHallId');
            const editCinemaId = document.getElementById('editCinemaId');
            const editHallName = document.getElementById('editHallName');
            const editStatusId = document.getElementById('editStatusId');
            
            if (editHallId) editHallId.value = hall.HallID;
            if (editCinemaId) editCinemaId.value = hall.CinemaID;
            if (editHallName) editHallName.value = hall.Name;
            if (editStatusId) editStatusId.value = hall.StatusID;
        }, 100);
    } else {
        // Modal đã tồn tại, chỉ cần đảm bảo selects đã có options
        setTimeout(() => {
            const editHallId = document.getElementById('editHallId');
            const editCinemaId = document.getElementById('editCinemaId');
            const editHallName = document.getElementById('editHallName');
            const editStatusId = document.getElementById('editStatusId');
            
            if (editHallId) editHallId.value = hall.HallID;
            if (editCinemaId) editCinemaId.value = hall.CinemaID;
            if (editHallName) editHallName.value = hall.Name;
            if (editStatusId) editStatusId.value = hall.StatusID;
        }, 100);
    }
    
    // Mở modal
    modal.classList.add('active');
}

/**
 * Handle update hall
 */
async function handleUpdateHall(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const hallId = formData.get('hall_id');
    
    if (!hallId) {
        showAlert('Lỗi: Không tìm thấy ID phòng chiếu', 'error');
        return;
    }
    
    const cinemaId = formData.get('cinema_id');
    const name = formData.get('name');
    const statusId = formData.get('status_id');
    
    if (!cinemaId || !name || !statusId) {
        showAlert('Vui lòng điền đầy đủ thông tin', 'error');
        return;
    }
    
    const data = {
        cinema_id: cinemaId,
        name: name.trim(),
        status_id: statusId
    };
    
    // Validation
    if (data.name.length > 50) {
        showAlert('Tên phòng chiếu không được vượt quá 50 ký tự', 'error');
        return;
    }
    
    try {
        await updateHall(hallId, data);
        showAlert('Cập nhật phòng chiếu thành công!', 'success');
        closeModal('editHallModal');
        // Reload lại trang để cập nhật bảng render bằng PHP
        setTimeout(() => window.location.reload(), 500);
    } catch (error) {
        console.error('Update hall error:', error);
        showAlert('Lỗi khi cập nhật: ' + error.message, 'error');
    }
}

/**
 * Handle create hall
 */
async function handleCreateHall(event) {
    event.preventDefault();
    event.stopPropagation();
    
    const form = event.target;
    if (!form) {
        console.error('Form not found');
        return;
    }
    
    const formData = new FormData(form);
    
    const cinemaId = formData.get('cinema_id');
    const name = formData.get('name');
    const statusId = formData.get('status_id');
    const seatCount = parseInt(formData.get('seat_count'), 10);
    
    console.log('Creating hall with data:', { cinemaId, name, statusId, seatCount });
    
    if (!cinemaId || !name || !statusId || !seatCount) {
        showAlert('Vui lòng điền đầy đủ thông tin (Rạp, Tên phòng, Trạng thái, Số ghế)', 'error');
        return;
    }
    
    const data = {
        cinema_id: cinemaId,
        name: name.trim(),
        status_id: statusId,
        seat_count: seatCount
    };
    
    // Validation
    if (data.name.length === 0) {
        showAlert('Tên phòng chiếu không được để trống', 'error');
        return;
    }
    
    if (data.name.length > 50) {
        showAlert('Tên phòng chiếu không được vượt quá 50 ký tự', 'error');
        return;
    }

    if (!Number.isInteger(data.seat_count) || data.seat_count < 1 || data.seat_count > 500) {
        showAlert('Số lượng ghế phải là số nguyên từ 1 đến 500', 'error');
        return;
    }
    
    // Disable submit button để tránh double submit
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Đang thêm...';
    }
    
    try {
        await createHall(data);
        showAlert('Thêm phòng chiếu thành công!', 'success');
        closeModal('addScreenModal');
        form.reset();
        
        // Reload lại trang để cập nhật bảng render bằng PHP
        setTimeout(() => window.location.reload(), 500);
    } catch (error) {
        console.error('Create hall error:', error);
        showAlert('Lỗi khi thêm phòng chiếu: ' + (error.message || 'Có lỗi xảy ra'), 'error');
    } finally {
        // Re-enable submit button
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Thêm';
        }
    }
}
