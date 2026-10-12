function openBillModal(el) {
  openModal("billModal");

  const tickets = JSON.parse(el.dataset.tickets || "[]");
  const combos = JSON.parse(el.dataset.combos || "[]");

  let ticketHtml = "";
  tickets.forEach((t) => {
    ticketHtml += `
            <div style="padding:6px 0;border-bottom:1px dashed #333;">
                🎟 ${t.movie_title} - ${t.row_name}${t.seat_number}
                <span style="float:right">${Number(t.price).toLocaleString()} ₫</span>
            </div>
        `;
  });

  let comboHtml = "";
  if (combos.length === 0) {
    comboHtml = `<div style="color:#888;">Không có combo</div>`;
  } else {
    combos.forEach((c) => {
      const total = c.quantity * c.price;
      comboHtml += `
                <div style="padding:6px 0;border-bottom:1px dashed #333;">
                    🍿 ${c.name} x${c.quantity}
                    <span style="float:right">${total.toLocaleString()} ₫</span>
                </div>
            `;
    });
  }

  document.getElementById("billDetailContent").innerHTML = `
        <div style="display:grid;gap:15px">

            <div>
                <strong>Khách hàng</strong><br>
                ${el.dataset.user}<br>
                <small style="color:#888">${el.dataset.email}</small>
            </div>

            <div>
                <strong>Vé</strong>
                ${ticketHtml}
            </div>

            <div>
                <strong>Combo</strong>
                ${comboHtml}
            </div>

            <div style="border-top:1px solid #444;padding-top:10px">
                <strong>Tổng tiền</strong><br>
                <span style="color:#e50914;font-size:18px;font-weight:bold">
                    ${Number(el.dataset.total).toLocaleString()} ₫
                </span>
            </div>

        </div>
    `;
}
