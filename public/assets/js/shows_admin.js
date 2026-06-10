/* =============================
   DATA
============================= */
let viewMode = "day";
let currentDate = new Date();
let selectedCinema = null;

const times = [
  "08:00",
  "10:00",
  "12:00",
  "14:00",
  "16:00",
  "18:00",
  "20:00",
  "22:00",
];

let shows = [];
let halls = [];
let cinemas = [];
let movies = [];
let rooms = [];

/* =============================
   LOAD DATA FROM PHP
============================= */
if (window.initialShows) {
  shows = window.initialShows.map((s) => ({
    show_id: s.show_id,
    movie: s.movie_title,
    room: s.hall_name,
    cinema_id: s.cinema_id,
    cinema: s.cinema_name,
    show_date: s.show_date,
    start_time: s.start_time,
    end_time: s.end_time,
    base_price: s.base_price,
    movie_id: s.movie_id,
    hall_id: s.hall_id,
    status: s.status,
  }));
}

if (window.initialHalls) {
  halls = window.initialHalls.map((h) => ({
    hall_id: h.hall_id,
    hall_name: h.name,
    cinema_id: h.cinema_id,
  }));
}

if (window.initialCinemas) {
  cinemas = window.initialCinemas.map((c) => ({
    cinema_id: c.CinemaID,
    cinema_name: c.Name,
  }));
}

if (window.initialMovies) {
  movies = window.initialMovies;
}

/* =============================
   UTILS
============================= */
function formatDate(d) {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${y}-${m}-${day}`;
}

function getShowColor(status) {
  switch (Number(status)) {
    case 0:
      return "#facc15";
    case 1:
      return "#22c55e";
    case -1:
      return "#e5e7eb";
    default:
      return "#e5e7eb";
  }
}

/* =============================
   THEATER SELECT
============================= */
function selectTheater(cinema_id) {
  selectedCinema = cinema_id;
  sessionStorage.setItem("selectedCinema", cinema_id);

  document.getElementById("theaterSelectView").style.display = "none";
  document.getElementById("calendarView").style.display = "block";

  const cinema = cinemas.find((c) => c.cinema_id == cinema_id);
  document.getElementById("theaterName").innerText = cinema?.cinema_name || "";

  rooms = halls.filter((h) => h.cinema_id == cinema_id);

  // Restore view mode từ session
  const savedMode = sessionStorage.getItem("selectedViewMode");
  if (savedMode) {
    viewMode = savedMode;
  }

  // Sync active button theo viewMode
  document.querySelectorAll(".view-btn").forEach((btn) => {
    btn.classList.toggle(
      "active",
      btn.textContent.trim().toLowerCase() === viewMode,
    );
  });

  renderHallOptions();
  render();
}

function backToTheater() {
  sessionStorage.removeItem("selectedCinema");
  sessionStorage.removeItem("selectedViewMode");
  document.getElementById("calendarView").style.display = "none";
  document.getElementById("theaterSelectView").style.display = "block";
}

/* =============================
   VIEW CONTROL
============================= */
function setView(mode, e) {
  viewMode = mode;
  sessionStorage.setItem("selectedViewMode", mode);

  document
    .querySelectorAll(".view-btn")
    .forEach((b) => b.classList.remove("active"));
  e.target.classList.add("active");
  render();
}

function prev() {
  if (viewMode === "day") currentDate.setDate(currentDate.getDate() - 1);
  else currentDate.setDate(currentDate.getDate() - 7);
  render();
}

function next() {
  if (viewMode === "day") currentDate.setDate(currentDate.getDate() + 1);
  else currentDate.setDate(currentDate.getDate() + 7);
  render();
}

function prev() {
  if (viewMode === "day") currentDate.setDate(currentDate.getDate() - 1);
  else currentDate.setDate(currentDate.getDate() - 7);
  render();
}

function next() {
  if (viewMode === "day") currentDate.setDate(currentDate.getDate() + 1);
  else currentDate.setDate(currentDate.getDate() + 7);
  render();
}

/* =============================
   RENDER
============================= */
function render() {
  if (viewMode === "day") {
    document.getElementById("currentDate").innerText =
      currentDate.toDateString();
    renderDay();
  } else {
    const start = new Date(currentDate);
    const dayIndex = (currentDate.getDay() + 6) % 7;
    start.setDate(currentDate.getDate() - dayIndex);
    const end = new Date(start);
    end.setDate(start.getDate() + 6);

    const fmt = (d) =>
      `${String(d.getDate()).padStart(2, "0")}/${String(d.getMonth() + 1).padStart(2, "0")}/${d.getFullYear()}`;

    document.getElementById("currentDate").innerText =
      `${fmt(start)} - ${fmt(end)}`;
    renderWeek();
  }
}

/* =============================
   DAY VIEW — RENDER
============================= */
function renderDay() {
  let html = `<div class="timeline">`;

  // header: time labels
  html += `<div class="time-header"><div></div>`;
  times.forEach((t) => {
    html += `<div class="time">${t}</div>`;
  });
  html += `</div>`;

  // rows: one per hall
  rooms.forEach((room) => {
    html += `<div class="timeline-row">`;
    html += `<div class="room-label">${room.hall_name}</div>`;

    times.forEach((time) => {
      html += `<div class="cell"
                    data-hall-id="${room.hall_id}"
                    data-room="${room.hall_name}"
                    data-time="${time}"></div>`;
    });

    html += `</div>`;
  });

  html += `</div>`;
  document.getElementById("calendar").innerHTML = html;

  renderEventsDay();
  attachDayEvents();
}

/* =============================
   DAY VIEW — EVENTS
============================= */
function renderEventsDay() {
  shows.forEach((show) => {
    if (show.show_date !== formatDate(currentDate)) return;
    if (selectedCinema && Number(show.cinema_id) !== Number(selectedCinema))
      return;

    const row = [...document.querySelectorAll(".timeline-row")].find((r) => {
      const firstCell = r.querySelector(".cell");
      return firstCell && firstCell.dataset.hallId == show.hall_id;
    });
    if (!row) return;

    const start = show.start_time.slice(0, 5);
    const end = show.end_time.slice(0, 5);
    const index = times.indexOf(start);
    if (index === -1) return;

    const cell = row.querySelectorAll(".cell")[index];
    const event = document.createElement("div");
    event.className = "event";
    event.style.background = getShowColor(show.status);
    event.innerHTML = `
      <div class="event-title">${show.movie}</div>
      <div class="event-time">${start} - ${end}</div>
      <div class="event-delete" data-id="${show.show_id}">✕</div>`;

    // click vào event → mở form EDIT
    event.onclick = (e) => {
      e.stopPropagation();
      openEditPopup(show);
    };

    cell.appendChild(event);
    cell.classList.add("has-show");
  });

  attachDelete();
}

/* =============================
   DAY VIEW — CELL CLICK → ADD
============================= */
function attachDayEvents() {
  document.querySelectorAll(".cell").forEach((cell) => {
    cell.onclick = () => {
      if (cell.classList.contains("has-show")) return;

      openAddPopup({
        hallId: cell.dataset.hallId,
        roomName: cell.dataset.room,
        time: cell.dataset.time,
        date: formatDate(currentDate),
        isWeek: false,
      });
    };
  });
}

/* =============================
   WEEK VIEW — RENDER
============================= */
function renderWeek() {
  const start = new Date(currentDate);
  const dayIndex = (currentDate.getDay() + 6) % 7;
  start.setDate(currentDate.getDate() - dayIndex);

  let html = `<div class="week-timeline">`;

  // header
  html += `<div class="week-header"><div class="time-header-cell"></div>`;
  for (let i = 0; i < 7; i++) {
    const d = new Date(start);
    d.setDate(start.getDate() + i);
    const weekday = [
      "Thứ 2",
      "Thứ 3",
      "Thứ 4",
      "Thứ 5",
      "Thứ 6",
      "Thứ 7",
      "Chủ nhật",
    ][i];
    html += `<div class="day-header">
               <div class="day-name">${weekday}</div>
               <div class="day-date">${String(d.getDate()).padStart(2, "0")}/${String(d.getMonth() + 1).padStart(2, "0")}/${d.getFullYear()}</div>
             </div>`;
  }
  html += `</div>`;

  // body
  html += `<div class="week-body">`;
  times.forEach((t) => {
    html += `<div class="week-row">`;
    html += `<div class="time-label">${t}</div>`;
    for (let i = 0; i < 7; i++) {
      const d = new Date(start);
      d.setDate(start.getDate() + i);
      const dateStr = formatDate(d);
      html += `<div class="week-cell" data-date="${dateStr}" data-time="${t}"></div>`;
    }
    html += `</div>`;
  });
  html += `</div></div>`;

  document.getElementById("calendar").innerHTML = html;
  renderEventsWeek(start);
  attachWeekEvents();
}

/* =============================
   WEEK VIEW — EVENTS
============================= */
function renderEventsWeek(startOfWeek) {
  shows.forEach((show) => {
    if (selectedCinema && show.cinema_id != selectedCinema) return;

    const showDate = new Date(show.show_date);
    const dayDiff = Math.floor(
      (showDate -
        new Date(
          startOfWeek.getFullYear(),
          startOfWeek.getMonth(),
          startOfWeek.getDate(),
        )) /
        (1000 * 60 * 60 * 24),
    );
    if (dayDiff < 0 || dayDiff > 6) return;

    const start = show.start_time.slice(0, 5);
    if (times.indexOf(start) === -1) return;

    const cell = document.querySelector(
      `.week-cell[data-date="${show.show_date}"][data-time="${start}"]`,
    );
    if (!cell) return;

    const ev = document.createElement("div");
    ev.className = "event";
    ev.style.background = getShowColor(show.status);
    ev.innerHTML = `
      <div class="event-title">${show.movie}</div>
      <div class="event-meta">Phòng: ${show.room} • ${start} - ${show.end_time.slice(0, 5)}</div>
      <div class="event-delete" data-id="${show.show_id}" title="Xóa">✕</div>`;

    // click vào event → mở form EDIT
    ev.onclick = (e) => {
      e.stopPropagation();
      openEditPopup(show);
    };

    cell.appendChild(ev);
    cell.classList.add("has-show");
  });

  attachDelete();
}

/* =============================
   WEEK VIEW — CELL CLICK → ADD
============================= */
function attachWeekEvents() {
  document.querySelectorAll(".week-cell").forEach((cell) => {
    cell.onclick = () => {
      if (cell.classList.contains("has-show")) return;

      currentDate = new Date(cell.dataset.date);

      openAddPopup({
        hallId: "",
        roomName: "",
        time: cell.dataset.time,
        date: cell.dataset.date,
        isWeek: true,
      });
    };
  });
}

/* =============================
   FORM ADD — MỞ / ĐÓNG
============================= */
function openAddPopup({ hallId, roomName, time, date, isWeek }) {
  // Reset toàn bộ form add
  document.getElementById("formAdd").reset();

  // Gán ngày
  document.getElementById("add_showDate").value = date;

  // Gán giờ bắt đầu
  document.getElementById("add_startTime").value = time;

  const hallIdField = document.getElementById("add_hallId");
  const hallDisplay = document.getElementById("add_hallDisplay");
  const hallSelectWrapper = document.getElementById("add_hallSelectWrapper");
  const hallSelect = document.getElementById("add_hallSelect");

  if (isWeek) {
    // Week mode: cho chọn phòng qua dropdown
    hallSelectWrapper.style.display = "block";
    hallDisplay.style.display = "none";

    // Sync hall_id ngay khi mở
    if (hallSelect.options.length > 0) {
      hallSelect.value = hallSelect.options[0].value;
      hallIdField.value = hallSelect.value;
    }

    hallSelect.onchange = () => {
      hallIdField.value = hallSelect.value;
    };
  } else {
    // Day mode: phòng cố định theo cell
    hallSelectWrapper.style.display = "none";
    hallDisplay.style.display = "block";

    hallIdField.value = String(hallId);
    hallDisplay.value = roomName;
  }

  document.getElementById("popupAdd").style.display = "block";
}

function closeAddPopup() {
  document.getElementById("popupAdd").style.display = "none";
  document.getElementById("formAdd").reset();
}

// Submit form Add: đảm bảo hall_id sync trước khi gửi
document.getElementById("formAdd").addEventListener("submit", function () {
  const hallSelectWrapper = document.getElementById("add_hallSelectWrapper");
  const hallSelect = document.getElementById("add_hallSelect");
  const hallIdField = document.getElementById("add_hallId");

  // Chỉ sync từ select khi week mode (wrapper đang hiện)
  if (hallSelectWrapper.style.display !== "none" && hallSelect.value) {
    hallIdField.value = hallSelect.value;
  }
  // Day mode: hallIdField đã được set đúng từ openAddPopup, không đụng vào
});

/* =============================
   FORM EDIT — MỞ / ĐÓNG
============================= */
function openEditPopup(show) {
  // Reset form edit
  document.getElementById("formEdit").reset();

  // Điền dữ liệu vào form edit
  document.getElementById("edit_showId").value = show.show_id;
  document.getElementById("edit_showDate").value = show.show_date;
  document.getElementById("edit_hallId").value = show.hall_id;
  document.getElementById("edit_hallDisplay").value = show.room;
  document.getElementById("edit_movieSelect").value = show.movie_id;
  document.getElementById("edit_startTime").value = show.start_time.slice(0, 5);
  document.getElementById("edit_endTime").value = show.end_time.slice(0, 5);
  document.getElementById("edit_price").value = show.base_price;
  document.getElementById("edit_status").value = show.status;

  // Nếu đã kết thúc, disable các field
  const finished = Number(show.status) === -1;
  document.getElementById("edit_movieSelect").disabled = finished;
  document.getElementById("edit_startTime").disabled = finished;
  document.getElementById("edit_price").disabled = finished;
  document.getElementById("edit_status").disabled = finished;
  document.getElementById("edit_btnUpdate").style.display = finished
    ? "none"
    : "inline-block";

  document.getElementById("popupEdit").style.display = "block";
}

function closeEditPopup() {
  document.getElementById("popupEdit").style.display = "none";
  document.getElementById("formEdit").reset();
}

function submitEdit() {
  const showId = document.getElementById("edit_showId").value;
  if (!showId) {
    alert("Không tìm thấy show id để cập nhật.");
    return;
  }

  const form = document.getElementById("formEdit");
  form.action = `../admin/index.php?page=shows&action=update&id=${encodeURIComponent(showId)}`;
  form.submit();
}

/* =============================
   DELETE
============================= */
function attachDelete() {
  document.querySelectorAll(".event-delete").forEach((btn) => {
    btn.onclick = (e) => {
      e.stopPropagation();
      const id = btn.dataset.id;

      // Tìm show tương ứng để check status
      const show = shows.find((s) => String(s.show_id) === String(id));
      if (show && Number(show.status) === -1) {
        alert("Không thể xóa suất chiếu đã kết thúc.");
        return;
      }

      if (!confirm("Bạn có chắc muốn xóa suất chiếu này?")) return;
      window.location.href = `../admin/index.php?page=shows&action=delete&id=${encodeURIComponent(id)}`;
    };
  });
}

/* =============================
   HALL OPTIONS (cho week add dropdown)
============================= */
function renderHallOptions() {
  const hallSelect = document.getElementById("add_hallSelect");
  if (!hallSelect) return;

  hallSelect.innerHTML = "";
  halls
    .filter((h) => h.cinema_id == selectedCinema)
    .forEach((room) => {
      const opt = document.createElement("option");
      opt.value = room.hall_id;
      opt.textContent = room.hall_name;
      hallSelect.appendChild(opt);
    });
}

/* =============================
   END TIME AUTO CALC
============================= */
function calcEndTime(movieSelectId, startTimeId, endTimeId) {
  const movie = document.getElementById(movieSelectId);
  const start = document.getElementById(startTimeId).value;
  const duration = movie.selectedOptions[0]?.dataset?.duration;

  if (!start || !duration) return;

  let [h, m] = start.split(":").map(Number);
  let total = h * 60 + m + parseInt(duration);

  document.getElementById(endTimeId).value =
    `${String(Math.floor(total / 60)).padStart(2, "0")}:${String(total % 60).padStart(2, "0")}`;
}

// Add form
document.getElementById("add_movieSelect").onchange = () =>
  calcEndTime("add_movieSelect", "add_startTime", "add_endTime");
document.getElementById("add_startTime").onchange = () =>
  calcEndTime("add_movieSelect", "add_startTime", "add_endTime");

// Edit form
document.getElementById("edit_movieSelect").onchange = () =>
  calcEndTime("edit_movieSelect", "edit_startTime", "edit_endTime");
document.getElementById("edit_startTime").onchange = () =>
  calcEndTime("edit_movieSelect", "edit_startTime", "edit_endTime");

/* =============================
   AUTO RESTORE STATE (sau redirect)
============================= */
(function restoreState() {
  const saved = sessionStorage.getItem("selectedCinema");
  const params = new URLSearchParams(window.location.search);
  const isRedirect =
    params.has("add") ||
    params.has("update") ||
    params.has("delete") ||
    params.has("error");

  if (saved && isRedirect) {
    selectTheater(saved);
  }
})();
