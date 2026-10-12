/**
 * JavaScript cho trang Lịch chiếu (User)
 */

let cinemasData = [];
let hallsData = [];
let showsData = [];
let currentCinemaId = null;
let currentMovieId = null;
const defaultPosterSvg = "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='300' height='450' viewBox='0 0 300 450'><rect width='300' height='450' fill='%23111111'/><rect x='15' y='15' width='270' height='420' rx='12' fill='%231b1b1b' stroke='%23333333'/><text x='50%25' y='48%25' fill='%23999999' font-size='22' font-family='Arial, sans-serif' text-anchor='middle'>No Image</text><text x='50%25' y='55%25' fill='%23666666' font-size='14' font-family='Arial, sans-serif' text-anchor='middle'>Cinemax</text></svg>";

// Load dữ liệu khi trang được tải
document.addEventListener('DOMContentLoaded', async function() {
    // Lấy cinema_id và movie_id từ URL
    const urlParams = new URLSearchParams(window.location.search);
    currentCinemaId = urlParams.get('cinema_id');
    currentMovieId = urlParams.get('movie_id') || urlParams.get('id');
    
    try {
        await loadAllData();
        renderShowtimes();
    } catch (error) {
        console.error('Lỗi khi load dữ liệu:', error);
        showError('Có lỗi xảy ra khi tải dữ liệu lịch chiếu');
    }
});

/**
 * Load tất cả dữ liệu cần thiết
 */
async function loadAllData() {
    try {
        const showFilters = {
            cinema_id: currentCinemaId || "",
            movie_id: currentMovieId || ""
        };

        if (!currentMovieId) {
            showFilters.show_date = getTodayIsoDate();
        }

        // Load cinemas và halls
        const [allCinemas, halls, userShows] = await Promise.all([
            getAllCinemas(),
            currentCinemaId ? getAllHalls(currentCinemaId) : Promise.resolve([]),
            getUserShows(showFilters)
        ]);
        // Chỉ giữ rạp đang hoạt động cho user
        cinemasData = allCinemas.filter(cinema => String(cinema.Status) === '1');
        hallsData = halls.filter(hall => String(hall.status ?? hall.Status ?? 0) === '1');
        showsData = userShows || [];
        updateMovieHeader();
    } catch (error) {
        throw new Error('Không thể tải dữ liệu: ' + error.message);
    }
}

function normalizePosterUrl(rawUrl) {
    const posterUrl = String(rawUrl || '').trim();
    if (!posterUrl) return defaultPosterSvg;
    if (/^https?:\/\//i.test(posterUrl) || posterUrl.startsWith('data:image/')) return posterUrl;
    if (posterUrl.startsWith('/cinemax/cinemax/')) return posterUrl;
    const clean = posterUrl.replace(/^\/+/, '');
    if (clean.startsWith('public/')) return `/cinemax/cinemax/${clean}`;
    if (clean.startsWith('assets/')) return `/cinemax/cinemax/public/${clean}`;
    if (clean.startsWith('uploads/')) return `/cinemax/cinemax/public/assets/${clean}`;
    return `/cinemax/cinemax/public/assets/uploads/movies/${clean}`;
}

function updateMovieHeader() {
    const titleEl = document.getElementById('showtimesMovieTitle');
    const posterEl = document.getElementById('showtimesPoster');
    const durationEl = document.getElementById('showtimesDuration');
    const directorEl = document.getElementById('showtimesDirector');
    const actorsEl = document.getElementById('showtimesActors');
    const genresEl = document.getElementById('showtimesGenres');
    const descriptionEl = document.getElementById('showtimesDescription');
    const metaEl = document.getElementById('showtimesMovieMeta');
    if (!titleEl || !posterEl || !durationEl || !directorEl || !actorsEl || !genresEl || !descriptionEl || !metaEl) return;

    let targetShow = null;
    if (currentMovieId) {
        targetShow = showsData.find((s) => String(s.movie_id ?? s.MovieID ?? '') === String(currentMovieId)) || null;
    }
    if (!targetShow && showsData.length > 0) {
        targetShow = showsData[0];
    }

    if (!targetShow) {
        return;
    }

    const movieTitle = targetShow.movie_title ?? targetShow.MovieTitle ?? titleEl.textContent ?? 'Lịch chiếu';
    const duration = targetShow.movie_duration_min ?? targetShow.duration_min ?? '';
    const director = targetShow.movie_director ?? targetShow.director ?? 'Đang cập nhật';
    const actors = targetShow.movie_actors ?? targetShow.actors ?? actorsEl.textContent;
    const genres = targetShow.movie_genres ?? targetShow.genre_names ?? genresEl.textContent;
    const description = targetShow.movie_description ?? targetShow.description ?? descriptionEl.textContent;
    const posterUrl = normalizePosterUrl(targetShow.movie_poster_url ?? targetShow.poster_url ?? '');

    titleEl.textContent = movieTitle;
    durationEl.textContent = duration ? `${duration} phút` : 'Đang cập nhật';
    directorEl.textContent = String(director || 'Đang cập nhật');
    actorsEl.textContent = String(actors || 'Đang cập nhật');
    genresEl.textContent = String(genres || 'Đang cập nhật');
    descriptionEl.textContent = String(description || 'Đang cập nhật');
    metaEl.hidden = false;
    posterEl.src = posterUrl;
    posterEl.onerror = () => {
        posterEl.onerror = null;
        posterEl.src = defaultPosterSvg;
    };
}

/**
 * Render lịch chiếu
 */
function renderShowtimes() {
    const container = document.getElementById('showtimesContent');
    if (!container) return;

    if (currentMovieId) {
        renderShowtimesByMovie();
        return;
    }
    
    // Nếu có cinema_id, hiển thị theo rạp
    if (currentCinemaId) {
        const cinema = cinemasData.find(c => c.CinemaID == currentCinemaId);
        if (cinema) {
            renderShowtimesByCinema(cinema, hallsData);
        }
    } else {
        // Hiển thị tất cả rạp
        renderAllCinemas();
    }
}

function renderShowtimesByMovie() {
    const container = document.getElementById('showtimesContent');
    if (!container) return;

    if (!showsData.length) {
        container.innerHTML = `
            <div style="text-align: center; padding: 40px; border: 1px dashed #444; border-radius: 8px; color: #aaa;">
                Hiện chưa có lịch chiếu trong hôm nay
            </div>
        `;
        return;
    }

    const grouped = {};
    showsData.forEach(show => {
        const date = show.show_date ?? show.ShowDate ?? '';
        const cinemaName = show.cinema_name ?? show.CinemaName ?? 'Rạp';
        if (!grouped[date]) grouped[date] = {};
        if (!grouped[date][cinemaName]) grouped[date][cinemaName] = [];
        grouped[date][cinemaName].push(show);
    });

    let html = '<div class="section-header"><h2>Lịch chiếu</h2></div>';
    Object.keys(grouped).sort().forEach(date => {
        html += `
            <div class="date-group">
                <h3 class="date-label">${formatDateFromString(date)}</h3>
        `;

        Object.keys(grouped[date]).sort().forEach(cinemaName => {
            html += `
                <div class="theater-group">
                    <div class="theater-name">${cinemaName}</div>
                    <div class="time-list">
            `;

            grouped[date][cinemaName].forEach(show => {
                const showId = show.show_id ?? show.ShowID ?? '';
                const hallId = show.hall_id ?? show.HallID ?? '';
                const hallName = show.hall_name ?? show.HallName ?? 'Phòng';
                const startTime = String(show.start_time ?? show.StartTime ?? '').substring(0, 5);
                const price = Number(show.base_price ?? show.BasePrice ?? 0).toLocaleString('vi-VN');

                html += `
                    <a href="index.php?page=seat_selection&show_id=${showId}&hall_id=${hallId}" class="time-btn">
                        <div>${hallName}</div>
                        ${startTime}<br>
                        <small>${price} ₫</small>
                    </a>
                `;
            });

            html += '</div></div>';
        });

        html += '</div>';
    });

    container.innerHTML = html;
}

/**
 * Render lịch chiếu theo rạp
 */
function renderShowtimesByCinema(cinema, halls) {
    const container = document.getElementById('showtimesContent');
    if (!container) return;

    const hallsWithShows = halls
        .map(hall => {
            const hallId = hall.HallID ?? hall.hall_id;
            const hallShows = showsData.filter(show => {
                const showHallId = show.hall_id ?? show.HallID ?? show.hallId;
                return String(showHallId) === String(hallId);
            });

            return { hall, hallShows };
        })
        .filter(item => item.hallShows.length > 0);
    
    let html = `
        <div class="section-header">
            <h2>Lịch chiếu - ${cinema.Name}</h2>
            <p style="color: #888; margin-top: 5px;">${cinema.Address}</p>
        </div>
        
        <div class="date-section">
            <h3 class="date-header">📅 Ngày: ${formatDate(new Date())}</h3>
    `;
    
    if (hallsWithShows.length === 0) {
        html += '<div style="text-align: center; padding: 40px; color: #888;">Hiện chưa có lịch chiếu trong hôm nay</div>';
    } else {
        hallsWithShows.forEach(({ hall, hallShows }) => {
            const hallId = hall.HallID ?? hall.hall_id;
            const hallName = hall.Name ?? hall.name ?? 'Phòng';
            html += `
                <div class="theater-block">
                    <div class="theater-name">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <path d="M3 9h18"></path>
                            <path d="M9 21V9"></path>
                        </svg>
                        ${hallName} (${hall.SeatCount || hall.seat_count || 0} ghế)
                    </div>
                    <div class="time-list" id="timeList-${hallId}">
                        ${hallShows.map(show => `
                                <a href="index.php?page=seat_selection&show_id=${show.show_id ?? show.ShowID ?? ''}&hall_id=${show.hall_id ?? show.HallID ?? ''}" class="time-btn">
                                    ${String(show.start_time ?? show.StartTime ?? '').substring(0, 5)}<br>
                                    <small>${Number(show.base_price ?? show.BasePrice ?? 0).toLocaleString('vi-VN')} ₫</small>
                                </a>
                              `).join('')}
                    </div>
                </div>
            `;
        });
    }
    
    html += '</div>';
    container.innerHTML = html;
    
}

/**
 * Render tất cả rạp
 */
function renderAllCinemas() {
    const container = document.getElementById('showtimesContent');
    if (!container) return;

    const cinemaIdsWithShows = new Set(
        showsData.map(show => String(show.cinema_id ?? show.CinemaID ?? ''))
    );
    const cinemasWithShows = cinemasData.filter(cinema => {
        const cinemaId = cinema.CinemaID ?? cinema.cinema_id;
        return cinemaIdsWithShows.has(String(cinemaId));
    });
    
    let html = '<div class="section-header"><h2>Chọn rạp chiếu</h2></div>';
    html += '<div class="theater-list">';
    
    if (cinemasWithShows.length === 0) {
        html += '<div style="text-align: center; padding: 40px; color: #888;">Hiện chưa có lịch chiếu trong hôm nay</div>';
    } else {
        cinemasWithShows.forEach(cinema => {
            html += `
                <div class="theater-card">
                    <h3>${cinema.Name}</h3>
                    <p><strong>Địa chỉ:</strong> ${cinema.Address}</p>
                    <p><strong>Số phòng:</strong> ${cinema.HallCount || 0}</p>
                    <a href="index.php?page=showtimes&cinema_id=${cinema.CinemaID}" class="btn-primary">
                        Xem lịch chiếu
                    </a>
                </div>
            `;
        });
    }
    
    html += '</div>';
    container.innerHTML = html;
}

/**
 * Format date
 */
function formatDate(date) {
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

function getTodayIsoDate() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function formatDateFromString(rawDate) {
    if (!rawDate) return '';
    const parts = String(rawDate).split('-');
    if (parts.length !== 3) return rawDate;
    return `${parts[2]}/${parts[1]}/${parts[0]}`;
}

/**
 * Show error message
 */
function showError(message) {
    const container = document.getElementById('showtimesContent');
    if (container) {
        container.innerHTML = `<div style="text-align: center; padding: 40px; color: #e50914;">${message}</div>`;
    }
}
