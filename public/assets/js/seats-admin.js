/**
 * JavaScript cho trang quản lý Sơ đồ ghế
 */

let seatsData = [];
let seatTypesData = [];
let hallData = null;
let currentHallId = null;
const seatTypeColorMap = {};

const seatTypePalette = [
    '#555555',
    '#e50914',
    '#4169e1',
    '#ffd700',
    '#8a2be2',
    '#00b894',
    '#ff7f50',
    '#20b2aa',
    '#f39c12',
    '#6c5ce7'
];

// Load dữ liệu khi trang được tải
document.addEventListener('DOMContentLoaded', async function() {
    // Lấy hall_id từ URL
    const urlParams = new URLSearchParams(window.location.search);
    currentHallId = urlParams.get('hall_id');
    
    if (!currentHallId) {
        showAlert('Không tìm thấy ID phòng chiếu', 'error');
        return;
    }
    
    try {
        await loadAllData();
        renderSeatLayout();
        populateLegend();
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
        [seatsData, seatTypesData, hallData] = await Promise.all([
            getSeatsByHall(currentHallId),
            getAllSeatTypes(),
            getHallById(currentHallId)
        ]);
        buildSeatTypeColorMap();
        
        // Cập nhật header với thông tin phòng
        if (hallData) {
            const headerTitle = document.querySelector('.seats h1');
            const hallInfo = document.getElementById('hallInfo');
            
            if (headerTitle) {
                headerTitle.textContent = `Sơ đồ ghế: ${hallData.Name}`;
            }
            
            if (hallInfo) {
                hallInfo.textContent = hallData.CinemaName || 'Đang tải...';
            }
        }
    } catch (error) {
        throw new Error('Không thể tải dữ liệu: ' + error.message);
    }
}

/**
 * Cập nhật header với thông tin phòng
 */
function updateHeader() {
    if (hallData) {
        const headerTitle = document.querySelector('.seats h1');
        if (headerTitle) {
            headerTitle.textContent = `Sơ đồ ghế: ${hallData.Name}`;
        }
    }
}

/**
 * Render sơ đồ ghế
 */
function renderSeatLayout() {
    const seatGrid = document.querySelector('.seat-grid');
    if (!seatGrid) return;
    
    seatGrid.innerHTML = '';
    
    if (seatsData.length === 0) {
        seatGrid.innerHTML = '<div style="text-align: center; padding: 40px; color: #666;">Chưa có ghế nào. Sử dụng nút "Tạo sơ đồ tự động" để tạo.</div>';
        return;
    }
    
    // Nhóm ghế theo hàng
    const seatsByRow = {};
    seatsData.forEach(seat => {
        const rowName = seat.RowName;
        if (!seatsByRow[rowName]) {
            seatsByRow[rowName] = [];
        }
        seatsByRow[rowName].push(seat);
    });
    
    // Sắp xếp các hàng
    const sortedRows = Object.keys(seatsByRow).sort();
    
    // Render từng hàng
    sortedRows.forEach(rowName => {
        const rowSeats = seatsByRow[rowName].sort((a, b) => Number(a.SeatNumber) - Number(b.SeatNumber));
        const seatMap = {};
        rowSeats.forEach(seat => {
            seatMap[Number(seat.SeatNumber)] = seat;
        });
        const maxSeatNumber = rowSeats.reduce((max, seat) => {
            const seatNum = Number(seat.SeatNumber);
            return seatNum > max ? seatNum : max;
        }, 0);
        
        const rowDiv = document.createElement('div');
        rowDiv.className = 'seat-row';
        
        // Label hàng
        const rowLabel = document.createElement('div');
        rowLabel.className = 'row-label';
        rowLabel.textContent = rowName;
        rowDiv.appendChild(rowLabel);
        
        // Render ghế theo trục số để giữ vị trí trống khi xóa (vd: mất A10 vẫn giữ chỗ A10)
        for (let seatNum = 1; seatNum <= maxSeatNumber; seatNum++) {
            const seat = seatMap[seatNum];
            if (!seat) {
                const emptySeat = document.createElement('div');
                emptySeat.className = 'seat-empty';
                emptySeat.title = `Vị trí trống ${rowName}${seatNum}`;
                rowDiv.appendChild(emptySeat);
                continue;
            }

            const seatWrapper = document.createElement('div');
            seatWrapper.style.position = 'relative';
            
            const seatItem = document.createElement('a');
            seatItem.href = '#';
            seatItem.className = `seat-item type-${seat.SeatTypeID}`;
            seatItem.style.backgroundColor = getSeatTypeColor(seat.SeatTypeID);
            seatItem.title = `Loại: ${seat.TypeName}`;
            seatItem.textContent = `${rowName}${seat.SeatNumber}`;
            seatItem.onclick = function(e) {
                e.preventDefault();
                changeSeatType(seat.SeatID);
            };
            
            // Nút xóa
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'btn-x';
            deleteBtn.title = 'Xóa';
            deleteBtn.innerHTML = '×';
            deleteBtn.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                deleteSeatHandler(seat.SeatID);
            };
            
            seatWrapper.appendChild(seatItem);
            seatWrapper.appendChild(deleteBtn);
            rowDiv.appendChild(seatWrapper);
        }
        
        seatGrid.appendChild(rowDiv);
    });
}

/**
 * Populate legend
 */
function populateLegend() {
    const legend = document.querySelector('.legend');
    if (!legend) return;
    
    // Lưu phần hướng dẫn nếu có
    const existingItems = Array.from(legend.querySelectorAll('.legend-item'));
    let instructionText = '👉 Click ghế để đổi loại | ❌ Click dấu X để xóa';
    existingItems.forEach(item => {
        if (item.textContent.includes('Click')) {
            instructionText = item.textContent;
        }
    });
    
    legend.innerHTML = '';

    const seatTypeCounts = {};
    seatsData.forEach(seat => {
        const key = String(seat.SeatTypeID);
        seatTypeCounts[key] = (seatTypeCounts[key] || 0) + 1;
    });
    
    // Thêm legend cho từng loại ghế
    seatTypesData.forEach(seatType => {
        const legendItem = document.createElement('div');
        legendItem.className = 'legend-item';
        legendItem.innerHTML = `
            <div class="dot type-${seatType.SeatTypeID}" style="background:${getSeatTypeColor(seatType.SeatTypeID)};"></div>
            ${seatType.TypeName} (${seatTypeCounts[String(seatType.SeatTypeID)] || 0})
        `;
        legend.appendChild(legendItem);
    });
    
    // Thêm lại phần hướng dẫn
    const instructionItem = document.createElement('div');
    instructionItem.className = 'legend-item';
    instructionItem.style.cssText = 'margin-left: 15px; border-left: 1px solid #444; padding-left: 15px;';
    instructionItem.textContent = instructionText;
    legend.appendChild(instructionItem);
}

/**
 * Change seat type (click vào ghế)
 */
async function changeSeatType(seatId) {
    try {
        if (!seatTypesData || seatTypesData.length <= 1) {
            showAlert('Hiện chỉ có 1 loại ghế, không thể đổi loại.', 'success');
            return;
        }

        const seat = seatsData.find(s => s.SeatID == seatId);
        if (!seat) {
            showAlert('Không tìm thấy ghế', 'error');
            return;
        }
        
        // Tìm loại ghế tiếp theo (hoặc quay về loại đầu tiên)
        const currentIndex = seatTypesData.findIndex(st => st.SeatTypeID == seat.SeatTypeID);
        const nextIndex = (currentIndex + 1) % seatTypesData.length;
        const nextSeatType = seatTypesData[nextIndex];
        
        // Cập nhật
        await updateSeat(seatId, { seat_type_id: nextSeatType.SeatTypeID });
        showAlert(`Đã đổi loại ghế thành ${nextSeatType.TypeName}`, 'success');
        
        // Reload data
        await loadAllData();
        renderSeatLayout();
    } catch (error) {
        console.error('Change seat type error:', error);
        showAlert('Lỗi khi đổi loại ghế: ' + error.message, 'error');
    }
}

/**
 * Delete seat (handler function)
 */
async function deleteSeatHandler(seatId) {
    if (!confirm('Bạn có chắc chắn muốn xóa ghế này?')) {
        return;
    }
    
    try {
        await deleteSeat(seatId);
        showAlert('Xóa ghế thành công!', 'success');
        await loadAllData();
        renderSeatLayout();
    } catch (error) {
        console.error('Delete seat error:', error);
        showAlert('Lỗi khi xóa ghế: ' + error.message, 'error');
    }
}

/**
 * Reset seat layout (xóa tất cả ghế)
 */
async function resetSeatLayout() {
    if (!confirm('CẢNH BÁO: Hành động này sẽ xóa sạch sơ đồ hiện tại để làm lại! Bạn có chắc chắn?')) {
        return;
    }
    
    try {
        await deleteAllSeatsByHall(currentHallId);
        showAlert('Xóa sơ đồ ghế thành công!', 'success');
        await loadAllData();
        renderSeatLayout();
    } catch (error) {
        console.error('Reset seat layout error:', error);
        showAlert('Lỗi khi xóa sơ đồ ghế: ' + error.message, 'error');
    }
}

/**
 * Show alert
 */
function showAlert(message, type = 'success') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    
    const content = document.querySelector('.seats .dashboard-content');
    if (content) {
        content.insertBefore(alert, content.firstChild);
        
        setTimeout(() => {
            alert.remove();
        }, 3000);
    }
}

/**
 * Open create seat modal
 */
function openCreateSeatModal() {
    // Tạo modal nếu chưa có
    let modal = document.getElementById('createSeatModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'createSeatModal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Thêm ghế mới</h2>
                    <button class="btn-close" onclick="closeModal('createSeatModal')">&times;</button>
                </div>
                <form id="createSeatForm" onsubmit="handleCreateSeat(event); return false;">
                    <input type="hidden" name="hall_id" value="${currentHallId}">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Hàng ghế (A-Z)</label>
                            <input type="text" name="row_name" maxlength="2" placeholder="VD: A, B, AA" required>
                        </div>
                        <div class="form-group">
                            <label>Số ghế</label>
                            <input type="number" name="seat_number" min="1" placeholder="VD: 1, 2, 3" required>
                        </div>
                        <div class="form-group">
                            <label>Loại ghế</label>
                            <select name="seat_type_id" id="createSeatTypeId" required></select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-action" onclick="closeModal('createSeatModal')">Hủy</button>
                        <button type="submit" class="btn-primary">Thêm</button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);
        populateCreateSeatSelect();
    }
    
    modal.classList.add('active');
}

/**
 * Populate select trong modal tạo ghế
 */
function populateCreateSeatSelect() {
    const select = document.getElementById('createSeatTypeId');
    if (!select) return;
    
    select.innerHTML = '<option value="">-- Chọn loại ghế --</option>';
    seatTypesData.forEach(seatType => {
        const option = document.createElement('option');
        option.value = seatType.SeatTypeID;
        option.textContent = seatType.TypeName;
        select.appendChild(option);
    });
}

/**
 * Handle create seat
 */
async function handleCreateSeat(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    const data = {
        hall_id: currentHallId,
        row_name: formData.get('row_name').trim().toUpperCase(),
        seat_number: formData.get('seat_number'),
        seat_type_id: formData.get('seat_type_id')
    };
    
    if (!data.row_name || !data.seat_number || !data.seat_type_id) {
        showAlert('Vui lòng điền đầy đủ thông tin', 'error');
        return;
    }
    
    try {
        await createSeat(data);
        showAlert('Thêm ghế thành công!', 'success');
        closeModal('createSeatModal');
        form.reset();
        await loadAllData();
        renderSeatLayout();
    } catch (error) {
        console.error('Create seat error:', error);
        showAlert('Lỗi khi thêm ghế: ' + error.message, 'error');
    }
}

/**
 * Open auto create modal
 */
function openAutoCreateModal() {
    let modal = document.getElementById('autoCreateSeatModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'autoCreateSeatModal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content" style="max-width: 500px;">
                <div class="modal-header">
                    <h2>Tạo sơ đồ ghế tự động</h2>
                    <button class="btn-close" onclick="closeModal('autoCreateSeatModal')">&times;</button>
                </div>
                <form id="autoCreateSeatForm" onsubmit="handleAutoCreateSeats(event); return false;">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Số hàng</label>
                            <input type="number" name="num_rows" min="1" max="26" value="10" required>
                        </div>
                        <div class="form-group">
                            <label>Số ghế mỗi hàng</label>
                            <input type="number" name="seats_per_row" min="1" max="50" value="12" required>
                        </div>
                        <div class="form-group">
                            <label>Loại ghế mặc định</label>
                            <select name="default_seat_type_id" id="defaultSeatTypeId" required></select>
                        </div>
                        <div style="font-size: 12px; color: #888; margin-top: 10px;">
                            * Sơ đồ sẽ được tạo từ hàng A đến hàng cuối cùng, mỗi hàng có số ghế như đã chọn.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-action" onclick="closeModal('autoCreateSeatModal')">Hủy</button>
                        <button type="submit" class="btn-primary">Tạo sơ đồ</button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);
        populateAutoCreateSelect();
    }
    
    modal.classList.add('active');
}

/**
 * Populate select trong modal auto create
 */
function populateAutoCreateSelect() {
    const select = document.getElementById('defaultSeatTypeId');
    if (!select) return;
    
    select.innerHTML = '<option value="">-- Chọn loại ghế --</option>';
    seatTypesData.forEach(seatType => {
        const option = document.createElement('option');
        option.value = seatType.SeatTypeID;
        option.textContent = seatType.TypeName;
        if (seatType.SeatTypeID == 1) {
            option.selected = true; // Mặc định chọn loại ghế đầu tiên
        }
        select.appendChild(option);
    });
}

/**
 * Handle auto create seats
 */
async function handleAutoCreateSeats(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    const numRows = parseInt(formData.get('num_rows'));
    const seatsPerRow = parseInt(formData.get('seats_per_row'));
    const defaultSeatTypeId = formData.get('default_seat_type_id');
    
    if (!numRows || !seatsPerRow || !defaultSeatTypeId) {
        showAlert('Vui lòng điền đầy đủ thông tin', 'error');
        return;
    }
    
    // Tạo danh sách ghế
    const seats = [];
    for (let row = 0; row < numRows; row++) {
        const rowName = String.fromCharCode(65 + row); // A, B, C, ...
        for (let seatNum = 1; seatNum <= seatsPerRow; seatNum++) {
            seats.push({
                seat_type_id: defaultSeatTypeId,
                row_name: rowName,
                seat_number: seatNum
            });
        }
    }
    
    try {
        const result = await createBulkSeats(currentHallId, seats);
        showAlert(`Tạo sơ đồ ghế thành công! (${result.data.success_count} ghế)`, 'success');
        if (result.data.errors && result.data.errors.length > 0) {
            console.warn('Một số ghế không thể tạo:', result.data.errors);
        }
        closeModal('autoCreateSeatModal');
        await loadAllData();
        renderSeatLayout();
    } catch (error) {
        console.error('Auto create seats error:', error);
        showAlert('Lỗi khi tạo sơ đồ ghế: ' + error.message, 'error');
    }
}

function buildSeatTypeColorMap() {
    Object.keys(seatTypeColorMap).forEach(key => delete seatTypeColorMap[key]);
    seatTypesData.forEach((seatType, index) => {
        const color = seatTypePalette[index % seatTypePalette.length];
        seatTypeColorMap[String(seatType.SeatTypeID)] = color;
    });
}

function getSeatTypeColor(seatTypeId) {
    const key = String(seatTypeId);
    if (!seatTypeColorMap[key]) {
        const fallbackIndex = Object.keys(seatTypeColorMap).length % seatTypePalette.length;
        seatTypeColorMap[key] = seatTypePalette[fallbackIndex];
    }
    return seatTypeColorMap[key];
}
