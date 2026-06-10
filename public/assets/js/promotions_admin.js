function openUpdatePromoModal(element) {
  openModal("updatePromoModal");

  setTimeout(() => {
    document.getElementById("update_promotion_id").value =
      element.dataset.promotionId || "";

    document.getElementById("update_code").value = element.dataset.code || "";

    document.getElementById("update_name").value = element.dataset.name || "";

    document.getElementById("update_discount_value").value =
      element.dataset.discountValue || "";

    document.getElementById("update_min_amount").value =
      element.dataset.minAmount || "0";

    document.getElementById("update_usage_limit").value =
      element.dataset.usageLimit || "0";

    document.getElementById("update_start_date").value =
      element.dataset.startDate || "";

    document.getElementById("update_end_date").value =
      element.dataset.endDate || "";

    const select = document.getElementById("update_discount_type");
    for (let opt of select.options) {
      opt.selected = opt.value === (element.dataset.discountType || "percent");
    }
  }, 0);
}

function confirmDeletePromo(element) {
  const promoId = element.dataset.promotionId;
  const confirmation = confirm(
    "Bạn có chắc chắn muốn xóa khuyến mãi này không?",
  );
  if (confirmation) {
    window.location.href = `../admin/index.php?page=promotions&action=delete&id=${promoId}`;
  }
}

function clearInlineErrors(form) {
  form
    .querySelectorAll(".field-error.client-error")
    .forEach((el) => el.remove());
  form
    .querySelectorAll(".input-error")
    .forEach((el) => el.classList.remove("input-error"));
}

function appendFieldError(input, message) {
  if (!input) return;
  input.classList.add("input-error");
  const error = document.createElement("div");
  error.className = "field-error client-error";
  error.textContent = message;
  input.insertAdjacentElement("afterend", error);
}

function validatePromotionForm(form) {
  clearInlineErrors(form);

  const code = form.querySelector('[name="code"]');
  const name = form.querySelector('[name="name"]');
  const discountType = form.querySelector('[name="discount_type"]');
  const discountValue = form.querySelector('[name="discount_value"]');
  const minAmount = form.querySelector('[name="min_amount"]');
  const usageLimit = form.querySelector('[name="usage_limit"]');
  const startDate = form.querySelector('[name="start_date"]');
  const endDate = form.querySelector('[name="end_date"]');

  let isValid = true;
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const codeVal = code?.value.trim() || "";
  const nameVal = name?.value.trim() || "";
  const discountTypeVal = discountType?.value || "";
  const discountValueVal = discountValue?.value.trim() || "";
  const minAmountVal = minAmount?.value.trim() || "0";
  const usageLimitVal = usageLimit?.value.trim() || "0";
  const startDateVal = startDate?.value.trim() || "";
  const endDateVal = endDate?.value.trim() || "";

  if (codeVal === "") {
    appendFieldError(code, "Vui lòng nhập mã code.");
    isValid = false;
  } else if (!/^[a-zA-Z0-9_-]+$/.test(codeVal)) {
    appendFieldError(
      code,
      "Mã code chỉ được chứa chữ, số, gạch dưới hoặc gạch ngang.",
    );
    isValid = false;
  }

  if (nameVal === "") {
    appendFieldError(name, "Vui lòng nhập tên khuyến mãi.");
    isValid = false;
  }

  if (!["percent", "fixed"].includes(discountTypeVal)) {
    appendFieldError(discountType, "Vui lòng chọn đơn vị giảm giá.");
    isValid = false;
  }

  if (
    discountValueVal === "" ||
    isNaN(discountValueVal) ||
    Number(discountValueVal) < 1
  ) {
    appendFieldError(discountValue, "Giá trị giảm phải lớn hơn hoặc bằng 1.");
    isValid = false;
  } else if (discountTypeVal === "percent" && Number(discountValueVal) > 100) {
    appendFieldError(discountValue, "Giảm theo % không được vượt quá 100.");
    isValid = false;
  }

  if (minAmountVal === "" || isNaN(minAmountVal) || Number(minAmountVal) < 0) {
    appendFieldError(minAmount, "Đơn tối thiểu không hợp lệ.");
    isValid = false;
  }

  if (
    usageLimitVal === "" ||
    isNaN(usageLimitVal) ||
    Number(usageLimitVal) < 0 ||
    !Number.isInteger(Number(usageLimitVal))
  ) {
    appendFieldError(usageLimit, "Giới hạn lượt dùng phải là số nguyên từ 0 trở lên.");
    isValid = false;
  }

  if (startDateVal === "") {
    appendFieldError(startDate, "Vui lòng chọn ngày bắt đầu.");
    isValid = false;
  }

  if (endDateVal === "") {
    appendFieldError(endDate, "Vui lòng chọn ngày kết thúc.");
    isValid = false;
  }

  if (form.id === "addPromoForm" && startDateVal !== "") {
    const start = new Date(startDateVal + "T00:00:00");
    if (start < today) {
      appendFieldError(
        startDate,
        "Ngày bắt đầu không được nhỏ hơn ngày hiện tại.",
      );
      isValid = false;
    }
  }

  if (startDateVal !== "" && endDateVal !== "") {
    const start = new Date(startDateVal + "T00:00:00");
    const end = new Date(endDateVal + "T00:00:00");
    if (end < start) {
      appendFieldError(
        endDate,
        "Ngày kết thúc không được nhỏ hơn ngày bắt đầu.",
      );
      isValid = false;
    }
  }

  return isValid;
}
