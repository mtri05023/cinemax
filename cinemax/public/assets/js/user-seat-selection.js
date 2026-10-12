let selectedTickets = [];
const MAX_TICKETS = 8;
function isolatedSeatNames() {
  const selected = new Set(selectedTickets.map(ticket => ticket.ticketId));
  const rows = new Map();
  document.querySelectorAll('#seatsContainer .seat').forEach(seat => {
    const row = seat.dataset.rowName;
    if (!rows.has(row)) rows.set(row, new Map());
    rows.get(row).set(Number(seat.dataset.seatNumber), seat);
  });
  const isolated = [];
  rows.forEach(row => row.forEach((seat, number) => {
    if (seat.dataset.status !== 'available' || selected.has(seat.dataset.ticketId)) return;
    let freeNeighbor = false, selectedNeighbor = false;
    [number - 1, number + 1].forEach(index => {
      const neighbor = row.get(index);
      if (!neighbor) return;
      if (selected.has(neighbor.dataset.ticketId)) selectedNeighbor = true;
      else if (neighbor.dataset.status === 'available') freeNeighbor = true;
    });
    if (selectedNeighbor && !freeNeighbor) isolated.push(seat.dataset.seatName);
  }));
  return isolated;
}
function updateSeatGapWarning() {
  const isolated = isolatedSeatNames();
  const warning = document.getElementById('seatGapError');
  if (warning) {
    warning.hidden = isolated.length === 0;
    warning.textContent = isolated.length ? `Không được để trống 1 ghế lẻ (${isolated.join(', ')}). Hãy chọn thêm ghế hoặc đổi vị trí để khoảng trống còn lại có ít nhất 2 ghế liền nhau.` : '';
  }
  const button = document.getElementById('btnContinue');
  if (button) {
    button.disabled = selectedTickets.length === 0 || isolated.length > 0;
    button.style.opacity = button.disabled ? '0.5' : '1';
    button.style.cursor = button.disabled ? 'not-allowed' : 'pointer';
  }
  return isolated.length === 0;
}
document.addEventListener('DOMContentLoaded', () => {
  updateSummary();
  document.getElementById('bookingForm')?.addEventListener('submit', event => {
    if (!selectedTickets.length || !updateSeatGapWarning()) event.preventDefault();
  });
});
async function refreshSeatAvailability() {
  const showId = new URLSearchParams(window.location.search).get('show_id');
  if (!showId || document.hidden) return;
  try {
    const response = await fetch(`index.php?page=seat_selection&action=seat_state&show_id=${encodeURIComponent(showId)}`, { cache: 'no-store' });
    const rows = await response.json();
    let changed = false;
    rows.forEach(ticket => {
      const seat = document.querySelector(`.seat[data-ticket-id="${Number(ticket.ticket_id)}"]`);
      if (!seat) return;
      seat.dataset.status = ticket.status;
      seat.onclick = ticket.status === 'available' ? () => toggleTicket(seat) : null;
      seat.classList.toggle('held', ['held','booked'].includes(ticket.status));
      seat.classList.toggle('sold', ['paid','used','unavailable'].includes(ticket.status));
      if (ticket.status !== 'available' && seat.classList.contains('selected')) { removeTicket(String(ticket.ticket_id)); changed = true; }
    });
    updateSeatGapWarning();
    if (changed) alert('Một ghế vừa được khách khác giữ hoặc đặt. Vui lòng chọn ghế còn trống.');
  } catch (error) { /* Reservation is validated again by the server on continue. */ }
}
setInterval(refreshSeatAvailability, 10000);

function toggleTicket(element) {
  const status = element.getAttribute("data-status");
  if (status !== 'available') return;
  if (
    status === "booked" ||
    status === "held" ||
    element.classList.contains("sold") ||
    element.classList.contains("held")
  )
    return;

  const ticketId = element.getAttribute("data-ticket-id");
  const seatName = element.getAttribute("data-seat-name");
  const price = parseFloat(element.getAttribute("data-price")) || 0;

  if (element.classList.contains("selected")) {
    element.classList.remove("selected");
    selectedTickets = selectedTickets.filter((t) => t.ticketId !== ticketId);
  } else {
    if (selectedTickets.length >= MAX_TICKETS) {
      alert(`Bạn chỉ được chọn tối đa ${MAX_TICKETS} vé.`);
      return;
    }
    element.classList.add("selected");
    selectedTickets.push({ ticketId, seatName, price });
  }
  updateSummary();
}

function removeTicket(ticketId) {
  const el = document.querySelector(`.seat[data-ticket-id="${ticketId}"]`);
  if (el) el.classList.remove("selected");
  selectedTickets = selectedTickets.filter((t) => t.ticketId !== ticketId);
  updateSummary();
}

function updateSummary() {
  const listContainer = document.getElementById("selectedSeats");
  const hiddenInputs = document.getElementById("hiddenInputs");
  const btnContinue = document.getElementById("btnContinue");
  const totalPriceEl = document.getElementById("totalPrice");

  if (!listContainer) return;

  listContainer.innerHTML = "";
  if (hiddenInputs) hiddenInputs.innerHTML = "";

  if (selectedTickets.length === 0) {
    updateSeatGapWarning();
    listContainer.innerHTML = '<p class="empty-message">Chưa chọn vé</p>';
    if (btnContinue) {
      btnContinue.disabled = true;
      btnContinue.style.opacity = "0.5";
      btnContinue.style.cursor = "not-allowed";
    }
    if (totalPriceEl) totalPriceEl.textContent = "0 ₫";
    return;
  }

  let total = 0;
  selectedTickets.forEach((ticket) => {
    const tag = document.createElement("div");
    tag.className = "seat-tag";
    tag.style.cssText =
      "background:#333;padding:5px 10px;border-radius:15px;font-size:13px;display:flex;align-items:center;gap:5px;border:1px solid #555;color:#fff;margin-bottom:5px;";
    tag.innerHTML = `${ticket.seatName} <span style="font-size:11px;color:#aaa;">(${ticket.price.toLocaleString("vi-VN")} ₫)</span><span style="cursor:pointer;color:#ff4444;font-weight:bold;margin-left:5px;" onclick="removeTicket('${ticket.ticketId}')">×</span>`;
    listContainer.appendChild(tag);

    if (hiddenInputs) {
      const inputTicket = document.createElement("input");
      inputTicket.type = "hidden";
      inputTicket.name = "ticket_ids[]";
      inputTicket.value = ticket.ticketId;
      hiddenInputs.appendChild(inputTicket);

      const inputName = document.createElement("input");
      inputName.type = "hidden";
      inputName.name = "seat_names[]";
      inputName.value = ticket.seatName;
      hiddenInputs.appendChild(inputName);
    }
    total += ticket.price;
  });

  if (totalPriceEl)
    totalPriceEl.textContent = total.toLocaleString("vi-VN") + " ₫";

  if (hiddenInputs) {
    const inputTotal = document.createElement("input");
    inputTotal.type = "hidden";
    inputTotal.name = "seat_total";
    inputTotal.value = String(total);
    hiddenInputs.appendChild(inputTotal);
  }

  if (btnContinue) {
    btnContinue.disabled = false;
    btnContinue.style.opacity = "1";
    btnContinue.style.cursor = "pointer";
  }
  updateSeatGapWarning();
}
