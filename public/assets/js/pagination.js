/**
 * ============================================
 *  PAGINATION COMPONENT - Phân trang tái sử dụng
 * ============================================
 *
 *  Hỗ trợ 2 chế độ:
 *    1. Server-side: Chuyển trang bằng URL (?page=2)
 *    2. Client-side:  Gọi callback khi chuyển trang (AJAX)
 *
 *  CÁCH DÙNG NHANH:
 *  ─────────────────
 *
 *  // 1) Client-side (AJAX) - phổ biến nhất
 *  const pager = new Pagination({
 *      container : '#my-pagination',
 *      totalItems: 120,
 *      limit     : 10,
 *      currentPage: 1,
 *      onPageChange: function(page) {
 *          // Gọi API load dữ liệu trang mới
 *          loadData(page);
 *      }
 *  });
 *
 *  // Cập nhật khi có data mới
 *  pager.update({ totalItems: 200, currentPage: 3 });
 *
 *  // 2) Server-side (URL redirect)
 *  const pager = new Pagination({
 *      container  : '#my-pagination',
 *      totalItems : 120,
 *      limit      : 10,
 *      currentPage: 1,
 *      mode       : 'server',
 *      baseUrl    : 'index.php?page=movies',
 *      pageParam  : 'p'           // => index.php?page=movies&p=2
 *  });
 *
 *  OPTIONS CHI TIẾT:
 *  ─────────────────
 *  | Option          | Type     | Default    | Mô tả                                      |
 *  |-----------------|----------|------------|---------------------------------------------|
 *  | container       | string   | (bắt buộc) | CSS selector cho container                  |
 *  | totalItems      | number   | 0          | Tổng số bản ghi                             |
 *  | limit           | number   | 10         | Số bản ghi / trang                          |
 *  | currentPage     | number   | 1          | Trang hiện tại                              |
 *  | maxVisiblePages | number   | 5          | Số nút trang tối đa hiển thị                |
 *  | mode            | string   | 'client'   | 'client' hoặc 'server'                      |
 *  | onPageChange    | function | null       | Callback khi chuyển trang (mode client)      |
 *  | baseUrl         | string   | ''         | URL cơ sở (mode server)                     |
 *  | pageParam       | string   | 'p'        | Tên param trang trên URL (mode server)       |
 *  | showInfo        | boolean  | true       | Hiển thị thông tin "Hiển thị x-y / z"        |
 *  | prevText        | string   | '« Trước'  | Text nút Trước                              |
 *  | nextText        | string   | 'Sau »'    | Text nút Sau                                |
 *  | infoFormat      | string   | (mặc định) | Template thông tin, dùng {from},{to},{total}  |
 *  | pageInfoFormat  | string   | (mặc định) | Template trang, dùng {current},{totalPages}   |
 *  | scrollToTop     | boolean  | false      | Cuộn lên đầu container khi chuyển trang      |
 *  | cssPrefix       | string   | ''         | Prefix CSS class (tránh xung đột)            |
 */

class Pagination {
    /**
     * @param {Object} options - Cấu hình pagination
     */
    constructor(options = {}) {
        // Validate container
        if (!options.container) {
            console.error('[Pagination] Thiếu option "container". Ví dụ: container: "#my-pagination"');
            return;
        }

        // Mặc định
        this.options = {
            container: null,
            totalItems: 0,
            limit: 10,
            currentPage: 1,
            maxVisiblePages: 5,
            mode: 'client',          // 'client' | 'server'
            onPageChange: null,       // callback(pageNumber)
            baseUrl: '',
            pageParam: 'p',
            showInfo: true,
            prevText: '&laquo; Trước',
            nextText: 'Sau &raquo;',
            infoFormat: 'Hiển thị {from}-{to} / {total} bản ghi',
            pageInfoFormat: 'Trang {current} / {totalPages}',
            scrollToTop: false,
            cssPrefix: '',
            ...options
        };

        // Tìm container
        this.containerEl = typeof this.options.container === 'string'
            ? document.querySelector(this.options.container)
            : this.options.container;

        if (!this.containerEl) {
            console.error(`[Pagination] Không tìm thấy container: "${this.options.container}"`);
            return;
        }

        // Tính toán & render
        this._calculate();
        this.render();
    }

    /* ──────────── TÍNH TOÁN ──────────── */

    _calculate() {
        const { totalItems, limit, currentPage, maxVisiblePages } = this.options;

        this.totalPages = Math.max(1, Math.ceil(totalItems / limit));
        this.currentPage = Math.max(1, Math.min(currentPage, this.totalPages));
        this.options.currentPage = this.currentPage;

        // Tính range các nút trang hiển thị
        const half = Math.floor(maxVisiblePages / 2);
        let startPage = Math.max(1, this.currentPage - half);
        let endPage = Math.min(this.totalPages, startPage + maxVisiblePages - 1);

        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }

        this.startPage = startPage;
        this.endPage = endPage;

        // Tính from/to cho info
        if (totalItems === 0) {
            this.from = 0;
            this.to = 0;
        } else {
            this.from = (this.currentPage - 1) * limit + 1;
            this.to = Math.min(this.currentPage * limit, totalItems);
        }
    }

    /* ──────────── RENDER ──────────── */

    render() {
        if (!this.containerEl) return;

        const { totalItems, showInfo, cssPrefix } = this.options;
        const pfx = cssPrefix;

        // Ẩn nếu không có dữ liệu hoặc chỉ 1 trang
        if (totalItems <= 0 || this.totalPages <= 1) {
            this.containerEl.innerHTML = '';
            return;
        }

        let html = '';

        // Info bar
        if (showInfo) {
            html += `<div class="${pfx}pagination-info">`;
            html += `<span>${this._formatInfo()}</span>`;
            html += `<span>${this._formatPageInfo()}</span>`;
            html += `</div>`;
        }

        // Pagination buttons
        html += `<div class="${pfx}pagination">`;

        // Nút Trước
        html += this.currentPage > 1
            ? this._createLink(this.currentPage - 1, this.options.prevText, `${pfx}page-link ${pfx}prev`)
            : `<span class="${pfx}page-link ${pfx}prev ${pfx}disabled">${this.options.prevText}</span>`;

        // Trang đầu + dots
        if (this.startPage > 1) {
            html += this._createLink(1, '1', `${pfx}page-link`);
            if (this.startPage > 2) {
                html += `<span class="${pfx}page-dots">...</span>`;
            }
        }

        // Các nút trang
        for (let i = this.startPage; i <= this.endPage; i++) {
            if (i === this.currentPage) {
                html += `<span class="${pfx}page-link ${pfx}active">${i}</span>`;
            } else {
                html += this._createLink(i, String(i), `${pfx}page-link`);
            }
        }

        // Dots + trang cuối
        if (this.endPage < this.totalPages) {
            if (this.endPage < this.totalPages - 1) {
                html += `<span class="${pfx}page-dots">...</span>`;
            }
            html += this._createLink(this.totalPages, String(this.totalPages), `${pfx}page-link`);
        }

        // Nút Sau
        html += this.currentPage < this.totalPages
            ? this._createLink(this.currentPage + 1, this.options.nextText, `${pfx}page-link ${pfx}next`)
            : `<span class="${pfx}page-link ${pfx}next ${pfx}disabled">${this.options.nextText}</span>`;

        html += `</div>`;

        this.containerEl.innerHTML = html;

        // Bind events (mode client)
        if (this.options.mode === 'client') {
            this._bindEvents();
        }
    }

    /* ──────────── TẠO LINK ──────────── */

    _createLink(page, text, className) {
        if (this.options.mode === 'server') {
            const url = this._buildUrl(page);
            return `<a href="${url}" class="${className}">${text}</a>`;
        }
        // Mode client: dùng data attribute
        return `<a href="javascript:void(0)" class="${className}" data-page="${page}">${text}</a>`;
    }

    _buildUrl(page) {
        const { baseUrl, pageParam } = this.options;

        if (!baseUrl) {
            // Auto-detect từ URL hiện tại
            const url = new URL(window.location.href);
            url.searchParams.set(pageParam, page);
            return url.toString();
        }

        // Nếu baseUrl đã có query string
        const separator = baseUrl.includes('?') ? '&' : '?';
        return `${baseUrl}${separator}${pageParam}=${page}`;
    }

    /* ──────────── EVENTS ──────────── */

    _bindEvents() {
        const links = this.containerEl.querySelectorAll('[data-page]');
        links.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = parseInt(link.getAttribute('data-page'), 10);
                if (page !== this.currentPage) {
                    this.goToPage(page);
                }
            });
        });
    }

    /* ──────────── NAVIGATION ──────────── */

    /**
     * Chuyển đến trang cụ thể
     * @param {number} page - Số trang
     */
    goToPage(page) {
        page = Math.max(1, Math.min(page, this.totalPages));
        if (page === this.currentPage) return;

        this.options.currentPage = page;
        this._calculate();
        this.render();

        // Scroll to top nếu cần
        if (this.options.scrollToTop && this.containerEl) {
            this.containerEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // Gọi callback
        if (typeof this.options.onPageChange === 'function') {
            this.options.onPageChange(page);
        }
    }

    /**
     * Trang tiếp theo
     */
    nextPage() {
        if (this.currentPage < this.totalPages) {
            this.goToPage(this.currentPage + 1);
        }
    }

    /**
     * Trang trước
     */
    prevPage() {
        if (this.currentPage > 1) {
            this.goToPage(this.currentPage - 1);
        }
    }

    /**
     * Trang đầu
     */
    firstPage() {
        this.goToPage(1);
    }

    /**
     * Trang cuối
     */
    lastPage() {
        this.goToPage(this.totalPages);
    }

    /* ──────────── CẬP NHẬT ──────────── */

    /**
     * Cập nhật options và render lại
     * @param {Object} newOptions - Các option muốn thay đổi
     *
     * Ví dụ: pager.update({ totalItems: 200, currentPage: 3 });
     */
    update(newOptions = {}) {
        Object.assign(this.options, newOptions);
        this._calculate();
        this.render();
    }

    /**
     * Hủy pagination, xóa nội dung container
     */
    destroy() {
        if (this.containerEl) {
            this.containerEl.innerHTML = '';
        }
    }

    /* ──────────── FORMAT ──────────── */

    _formatInfo() {
        return this.options.infoFormat
            .replace('{from}', this.from)
            .replace('{to}', this.to)
            .replace('{total}', this.options.totalItems);
    }

    _formatPageInfo() {
        return this.options.pageInfoFormat
            .replace('{current}', this.currentPage)
            .replace('{totalPages}', this.totalPages);
    }

    /* ──────────── GETTERS ──────────── */

    /**
     * Lấy thông tin phân trang hiện tại
     * @returns {Object} { currentPage, totalPages, totalItems, limit, from, to }
     */
    getInfo() {
        return {
            currentPage: this.currentPage,
            totalPages: this.totalPages,
            totalItems: this.options.totalItems,
            limit: this.options.limit,
            from: this.from,
            to: this.to
        };
    }

    /**
     * Lấy offset cho query SQL (LIMIT offset, limit)
     * @returns {number}
     */
    getOffset() {
        return (this.currentPage - 1) * this.options.limit;
    }
}

/* ──────────────────────────────────────────────
   HELPER: Tạo pagination nhanh bằng function
   ────────────────────────────────────────────── */

/**
 * Hàm tạo nhanh Pagination
 * @param {string} selector - CSS selector
 * @param {Object} options  - Options (không cần truyền container)
 * @returns {Pagination}
 *
 * Ví dụ:
 *   const pager = createPagination('#pagination', {
 *       totalItems: 100,
 *       limit: 10,
 *       onPageChange: (page) => loadData(page)
 *   });
 */
function createPagination(selector, options = {}) {
    return new Pagination({ container: selector, ...options });
}

