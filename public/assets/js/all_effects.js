/*
=========================================================
FILE HIỆU ỨNG CHUNG (UI) - ĐÃ CẬP NHẬT
=========================================================
*/

document.addEventListener("DOMContentLoaded", function () {
  // --- LOGIC Mobile Menu ---
  const mobileMenuBtn = document.getElementById("mobileMenuBtn");
  const navLinks = document.querySelector(".nav-links");

  if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener("click", function () {
      if (navLinks) {
        navLinks.classList.toggle("active");
      }
      this.classList.toggle("active");
    });
  }

  // --- LOGIC Slider Trang chủ ---
  const hero = document.querySelector(".hero");
  const slides = hero ? hero.querySelectorAll(".hero-slide") : [];
  const dots = hero ? hero.querySelectorAll(".hero-dots .dot") : [];
  let currentSlide = 0;
  let slideTimer = null;
  const slideDelay = 3000;

  function showSlide(index) {
    if (slides.length === 0) return;

    currentSlide = (index + slides.length) % slides.length;

    slides.forEach((slide, slideIndex) => {
      const isActive = slideIndex === currentSlide;
      slide.classList.toggle("active", isActive);
      slide.setAttribute("aria-hidden", String(!isActive));
    });

    dots.forEach((dot, dotIndex) => {
      const isActive = dotIndex === currentSlide;
      dot.classList.toggle("active", isActive);
      dot.setAttribute("aria-selected", String(isActive));
    });
  }

  function stopSlider() {
    if (slideTimer) {
      window.clearInterval(slideTimer);
      slideTimer = null;
    }
  }

  function startSlider() {
    stopSlider();

    if (
      slides.length > 1 &&
      !window.matchMedia("(prefers-reduced-motion: reduce)").matches
    ) {
      slideTimer = window.setInterval(
        () => showSlide(currentSlide + 1),
        slideDelay,
      );
    }
  }

  if (hero && slides.length > 0) {
    showSlide(0);

    dots.forEach((dot, index) => {
      dot.addEventListener("click", () => {
        showSlide(index);
        startSlider();
      });
    });

    hero.addEventListener("focusin", stopSlider);
    hero.addEventListener("focusout", (event) => {
      if (!hero.contains(event.relatedTarget)) startSlider();
    });
    hero.addEventListener("keydown", (event) => {
      if (event.key === "ArrowLeft" || event.key === "ArrowRight") {
        event.preventDefault();
        showSlide(currentSlide + (event.key === "ArrowRight" ? 1 : -1));
      }
    });

    document.addEventListener("visibilitychange", () => {
      if (document.hidden) stopSlider();
      else startSlider();
    });

    startSlider();
  }

  // ===========================================
  // LOGIC CHO CLICK DROPDOWN
  // ===========================================
  const userMenuButton = document.getElementById("userMenuButton");
  const userMenuContent = document.getElementById("userMenuContent");
  const userMenuDropdown = document.getElementById("userMenuDropdown");

  if (userMenuButton && userMenuContent) {
    // 1. Khi nhấn vào nút (Tên người dùng)
    userMenuButton.addEventListener("click", function (event) {
      // Thêm/Xóa class 'active'
      userMenuContent.classList.toggle("active");
      userMenuDropdown.classList.toggle("active");
      event.stopPropagation();
    });
  }

  // 2. Khi nhấn ra ngoài (window)
  window.addEventListener("click", function (event) {
    if (userMenuContent && userMenuContent.classList.contains("active")) {
      if (!userMenuButton.contains(event.target)) {
        userMenuContent.classList.remove("active");
        userMenuDropdown.classList.remove("active");
      }
    }
  });
}); // --- Kết thúc DOMContentLoaded ---

// =========================================================
// CÁC HÀM TIỆN ÍCH GIAO DIỆN
// =========================================================

function openModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add("active");
  }
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove("active");
  }
}

document.addEventListener("click", function (e) {
  if (e.target.classList.contains("modal")) {
    e.target.classList.remove("active");
  }
});

function togglePassword(inputId) {
  const passwordInput = document.getElementById(inputId);
  if (passwordInput) {
    const type = passwordInput.type === "password" ? "text" : "password";
    passwordInput.type = type;
  }
}

function showNotification(message, type = "success") {
  const notification = document.createElement("div");
  notification.className = `notification notification-${type}`;
  notification.innerHTML = `
        <div class="notification-content">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                ${type === "success" ? '<polyline points="20 6 9 17 4 12"></polyline>' : '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>'}
            </svg>
            <span>${message}</span>
        </div>
    `;
  document.body.appendChild(notification);
  setTimeout(() => {
    notification.classList.add("show");
  }, 100);
  setTimeout(() => {
    notification.classList.remove("show");
    setTimeout(() => {
      notification.remove();
    }, 3000);
  }, 3000);
}

document.addEventListener("DOMContentLoaded", function () {
  const alertBox = document.getElementById("autoAlert");

  if (alertBox) {
    setTimeout(() => {
      alertBox.style.opacity = "0";

      setTimeout(() => {
        alertBox.remove();
      }, 500); // khớp với transition CSS
    }, 3000);
  }
});

// --- TOGGLE USER MENU (Header dropdown khi đã đăng nhập) ---
function toggleUserMenu() {
  const menu = document.getElementById("userMenu");
  if (menu) {
    menu.classList.toggle("show");
  }
}

// Đóng user menu khi click ra ngoài
document.addEventListener("click", function (e) {
  const menu = document.getElementById("userMenu");
  const btn = e.target.closest(".btn-user");
  if (menu && !btn && !menu.contains(e.target)) {
    menu.classList.remove("show");
  }
});

// Permission
document.querySelectorAll(".toggle").forEach((toggle) => {
  toggle.addEventListener("click", () => {
    const groupRow = toggle.closest("tr");
    let next = groupRow.nextElementSibling;

    const isOpen = toggle.textContent === "▼";
    toggle.textContent = isOpen ? "▶" : "▼";

    while (next && next.classList.contains("perm-child")) {
      next.style.display = isOpen ? "none" : "table-row";
      next = next.nextElementSibling;
    }
  });
});


