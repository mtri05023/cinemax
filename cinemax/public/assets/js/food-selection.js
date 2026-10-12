let selectedFoods = {};
const baseSeatTotal = Number(window.foodSelectionData?.seatTotal || 0);

function updateFood(btn, change) {
  const controlDiv = btn.closest(".qty-control");
  if (!controlDiv) return;

  const id = String(controlDiv.getAttribute("data-id") || "");
  const price = Number(controlDiv.getAttribute("data-price") || 0);
  const name = String(controlDiv.getAttribute("data-name") || "Combo");
  const display = controlDiv.querySelector(".qty-val");

  if (!id || !display) return;

  const currentQty = selectedFoods[id] ? Number(selectedFoods[id].qty) : 0;
  let newQty = currentQty + change;
  if (newQty < 0) newQty = 0;

  if (newQty === 0) {
    delete selectedFoods[id];
    display.style.color = "#fff";
  } else {
    selectedFoods[id] = { qty: newQty, price, name };
    display.style.color = "#e50914";
  }

  display.textContent = String(newQty);
  renderSummary();
}

function renderSummary() {
  const foodListContainer = document.getElementById("selectedFoodList");
  const foodContainerBlock = document.getElementById("selectedFoodContainer");
  const foodTotalDisplay = document.getElementById("foodTotalDisplay");
  const grandTotalDisplay = document.getElementById("grandTotalDisplay");
  const foodInputsDiv = document.getElementById("foodInputs");
  const foodTotalInput = document.getElementById("foodTotalInput");
  const grandTotalInput = document.getElementById("grandTotalInput");
  const foodsJsonInput = document.getElementById("foodsJsonInput");

  let foodTotal = 0;
  let htmlList = "";
  let htmlInputs = "";
  const foodsPayload = [];

  Object.entries(selectedFoods).forEach(([id, item]) => {
    const itemTotal = Number(item.qty) * Number(item.price);
    foodTotal += itemTotal;

    htmlList += `
            <div class="info-row" style="font-size: 13px; margin-bottom: 5px; border-bottom: 1px dashed #333; padding-bottom: 5px;">
                <span style="color: #fff;">${item.name} <strong style="color: #e50914;">x${item.qty}</strong></span>
                <span>${itemTotal.toLocaleString("vi-VN")} ₫</span>
            </div>
        `;
    htmlInputs += `<input type="hidden" name="foods[${id}]" value="${item.qty}">`;
    foodsPayload.push({
      combo_id: Number(id),
      quantity: Number(item.qty),
      price: Number(item.price),
    });
  });

  if (foodTotal > 0) {
    foodContainerBlock.style.display = "block";
    foodListContainer.innerHTML = htmlList;
  } else {
    foodContainerBlock.style.display = "none";
    foodListContainer.innerHTML = "";
  }

  const grandTotal = baseSeatTotal + foodTotal;
  foodTotalDisplay.textContent = `${foodTotal.toLocaleString("vi-VN")} ₫`;
  grandTotalDisplay.textContent = `${grandTotal.toLocaleString("vi-VN")} ₫`;
  foodInputsDiv.innerHTML = htmlInputs;

  if (foodTotalInput) foodTotalInput.value = String(foodTotal);
  if (grandTotalInput) grandTotalInput.value = String(grandTotal);
  if (foodsJsonInput) foodsJsonInput.value = JSON.stringify(foodsPayload);
}

document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".qty-control .btn-qty.minus").forEach((btn) => {
    btn.addEventListener("click", () => updateFood(btn, -1));
  });
  document.querySelectorAll(".qty-control .btn-qty.plus").forEach((btn) => {
    btn.addEventListener("click", () => updateFood(btn, 1));
  });
  renderSummary();
});
