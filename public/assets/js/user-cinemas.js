/**
 * JavaScript cho trang Rạp chiếu (User)
 */

let cinemasData = [];
let locationsData = [];

// Load dữ liệu khi trang được tải
document.addEventListener('DOMContentLoaded', async function() {
    try {
        await Promise.all([loadCinemas(), loadLocations()]);
        renderLocationFilter();
        renderCinemas();
    } catch (error) {
        console.error('Lỗi khi load dữ liệu:', error);
        showError('Có lỗi xảy ra khi tải dữ liệu rạp chiếu');
    }
});

/**
 * Load danh sách rạp chiếu
 */
async function loadCinemas() {
    try {
        const allCinemas = await getAllCinemas();
        // Chỉ hiển thị rạp đang hoạt động cho user
        cinemasData = allCinemas.filter(cinema => String(cinema.Status) === '1');
    } catch (error) {
        throw new Error('Không thể tải dữ liệu rạp chiếu: ' + error.message);
    }
}

/**
 * Load danh sách khu vực (locations)
 */
async function loadLocations() {
    try {
        locationsData = await getAllLocations();
    } catch (error) {
        console.error('Không thể tải danh sách khu vực:', error);
        locationsData = [];
    }
}

/**
 * Render dropdown chọn khu vực
 */
function renderLocationFilter() {
    const select = document.getElementById('locationFilter');
    if (!select) return;

    // Option mặc định
    select.innerHTML = '<option value="">Tất cả khu vực</option>';

    if (!locationsData || locationsData.length === 0) {
        return;
    }

    locationsData.forEach(location => {
        const option = document.createElement('option');
        option.value = location.LocationID;
        option.textContent = location.Name;
        select.appendChild(option);
    });

    // Gắn sự kiện change để lọc rạp
    select.addEventListener('change', function() {
        renderCinemas();
    });
}

/**
 * Render danh sách rạp chiếu (có filter theo khu vực)
 */
function renderCinemas() {
    const theaterList = document.querySelector('.theater-list');
    if (!theaterList) return;
    
    theaterList.innerHTML = '';
    
    if (!cinemasData || cinemasData.length === 0) {
        theaterList.innerHTML = '<div style="text-align: center; padding: 40px; color: #888;">Chưa có rạp chiếu nào</div>';
        return;
    }

    // Lọc theo khu vực nếu user chọn
    const locationSelect = document.getElementById('locationFilter');
    const selectedLocationId = locationSelect ? locationSelect.value : '';

    let filteredCinemas = cinemasData;
    if (selectedLocationId) {
        filteredCinemas = cinemasData.filter(cinema =>
            String(cinema.LocationID) === String(selectedLocationId)
        );
    }

    if (filteredCinemas.length === 0) {
        theaterList.innerHTML = '<div style="text-align: center; padding: 40px; color: #888;">Không có rạp nào trong khu vực này</div>';
        return;
    }
    
    filteredCinemas.forEach(cinema => {
        const theaterCard = document.createElement('div');
        theaterCard.className = 'theater-card';
        theaterCard.innerHTML = `
            <h3>${cinema.Name}</h3>
            <p><strong>Địa chỉ:</strong> ${cinema.Address}</p>
            <p><strong>Thành phố / Khu vực:</strong> ${cinema.LocationName || 'N/A'}</p>
            <p><strong>Số phòng:</strong> ${cinema.HallCount || 0}</p>
            <a href="index.php?page=showtimes&cinema_id=${cinema.CinemaID}" class="btn-primary">
                Xem lịch chiếu
            </a>
        `;
        theaterList.appendChild(theaterCard);
    });
}

/**
 * Show error message
 */
function showError(message) {
    const theaterList = document.querySelector('.theater-list');
    if (theaterList) {
        theaterList.innerHTML = `<div style="text-align: center; padding: 40px; color: #e50914;">${message}</div>`;
    }
}
