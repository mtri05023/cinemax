/**
 * JavaScript cho trang quản lý Loại ghế
 * Phần render bảng đã chuyển sang PHP.
 * JS chỉ dùng cho CRUD (thêm / sửa / xóa) và modal.
 */

// Form thêm mới đã dùng inline onsubmit trong view (seat_types.php),
// không gắn addEventListener lần nữa để tránh submit bị chạy 2 lần.

/**
 * Edit seat type
 */
async function editSeatType(id) {
    try {
        const seatType = await getSeatTypeById(id);
        if (!seatType) {
            showAlert('Không tìm thấy loại ghế', 'error');
            return;
        }
        
        // Tính phụ thu từ PriceMultiplier
        const basePrice = 100000;
        const priceSurcharge = Number(((seatType.PriceMultiplier - 1) * basePrice).toFixed(2));
        
        openEditModal(seatType, priceSurcharge);
    } catch (error) {
        showAlert('Lỗi khi lấy thông tin loại ghế: ' + error.message, 'error');
    }
}

/**
 * Delete seat type
 */
async function deleteSeatType(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa loại ghế này?')) {
        return;
    }
    
    try {
        await deleteAPI('seat_types', 'delete', id);
        showAlert('Xóa loại ghế thành công!', 'success');
        // Reload lại trang để cập nhật bảng render bằng PHP
        setTimeout(() => window.location.reload(), 500);
    } catch (error) {
        showAlert('Lỗi khi xóa loại ghế: ' + error.message, 'error');
    }
}

/**
 * Open edit modal
 */
function openEditModal(seatType, priceSurcharge) {
    // Tạo modal nếu chưa có
    let modal = document.getElementById('editSeatTypeModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'editSeatTypeModal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Cập nhật loại ghế</h2>
                    <button class="btn-close" onclick="closeModal('editSeatTypeModal')">&times;</button>
                </div>
                <form id="editSeatTypeForm" onsubmit="handleUpdateSeatType(event); return false;">
                    <input type="hidden" name="seat_type_id" id="editSeatTypeId">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Tên loại ghế</label>
                            <input type="text" name="type_name" id="editTypeName" placeholder="VD: VIP, Couple" required>
                        </div>
                        <div class="form-group">
                            <label>Phụ thu (VNĐ)</label>
                            <input type="number" name="price_surcharge" id="editPriceSurcharge" value="0" required step="0.01" min="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-action" onclick="closeModal('editSeatTypeModal')">Hủy</button>
                        <button type="submit" class="btn-primary">Cập nhật</button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // Điền dữ liệu
    document.getElementById('editSeatTypeId').value = seatType.SeatTypeID;
    document.getElementById('editTypeName').value = seatType.TypeName;
    document.getElementById('editPriceSurcharge').value = priceSurcharge;
    
    // Mở modal
    modal.classList.add('active');
}

/**
 * Handle update seat type
 */
async function handleUpdateSeatType(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const seatTypeId = formData.get('seat_type_id');
    
    const data = {
        type_name: formData.get('type_name'),
        price_surcharge: formData.get('price_surcharge')
    };
    
    try {
        await updateSeatType(seatTypeId, data);
        showAlert('Cập nhật loại ghế thành công!', 'success');
        closeModal('editSeatTypeModal');
        // Reload lại trang để cập nhật bảng render bằng PHP
        setTimeout(() => window.location.reload(), 500);
    } catch (error) {
        showAlert('Lỗi khi cập nhật: ' + error.message, 'error');
    }
}

/**
 * Handle create seat type
 */
async function handleCreateSeatType(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    const data = {
        type_name: formData.get('type_name'),
        price_surcharge: formData.get('price_surcharge')
    };
    
    try {
        await createSeatType(data);
        showAlert('Thêm loại ghế thành công!', 'success');
        closeModal('addSeatTypeModal');
        form.reset();
        // Reload lại trang để cập nhật bảng render bằng PHP
        setTimeout(() => window.location.reload(), 500);
    } catch (error) {
        showAlert('Lỗi khi thêm loại ghế: ' + error.message, 'error');
    }
}

/**
 * Show alert
 */
function showAlert(message, type = 'success') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    
    const content = document.querySelector('.seat_types .dashboard-content');
    if (content) {
        content.insertBefore(alert, content.firstChild);
        
        setTimeout(() => {
            alert.remove();
        }, 3000);
    }
}
