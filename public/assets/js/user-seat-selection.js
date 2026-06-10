let selectedTickets = [];
const MAX_TICKETS = 8;

function toggleTicket(element) {
  const status = element.getAttribute("data-status");
  console.log(
    "clicked",
    element.getAttribute("data-status"),
    element.className,
  );
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
}
