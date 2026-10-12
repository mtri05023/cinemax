# 📄 Pagination Component - Hướng dẫn sử dụng

Component phân trang tái sử dụng cho toàn bộ dự án Cinemax.  
Hỗ trợ cả **PHP (server-side)** và **JavaScript (client-side/AJAX)**.

---

## 📁 Cấu trúc file

```
Cinemax/
├── views/
│   └── components/
│       └── pagination.php          ← PHP Component chính (hàm renderPagination)
├── public/assets/
│   ├── css/
│   │   └── pagination.css          ← CSS dùng chung
│   └── js/
│       └── pagination.js           ← JS Component (class Pagination)
```

---

## 🟢 CÁCH 1: PHP Server-Side (chuyển trang bằng URL)

### Ví dụ cơ bản

```php
// Trong file page bất kỳ (vd: views/admin/pages/movies.php)
require_once __DIR__ . '/../../components/pagination.php';

// Giả sử bạn đã query database
$totalRecords = 120;
$limit = 10;
$currentPage = $_GET['p'] ?? 1;

// Render pagination
echo renderPagination([
    'totalItems'  => $totalRecords,
    'limit'       => $limit,
    'currentPage' => $currentPage,
]);
```

### Ví dụ đầy đủ với query database

```php
<?php
require_once __DIR__ . '/../../components/pagination.php';

$limit = 10;
$currentPage = (int)($_GET['p'] ?? 1);
$offset = ($currentPage - 1) * $limit;

// Query đếm tổng
$totalRecords = $model->countAll();

// Query lấy data với LIMIT
$data = $model->getAll($limit, $offset);
?>

<!-- Hiển thị table/data ở đây -->
<table>
    <?php foreach ($data as $row): ?>
        <tr><td><?= $row['name'] ?></td></tr>
    <?php endforeach; ?>
</table>

<!-- Render pagination -->
<?= renderPagination([
    'totalItems'  => $totalRecords,
    'limit'       => $limit,
    'currentPage' => $currentPage,
]) ?>
```

### Ví dụ dùng include (tương thích cách cũ)

```php
<?php
$totalItems  = 120;
$limit       = 10;
$currentPage = $_GET['p'] ?? 1;
$totalPages  = ceil($totalItems / $limit);

include __DIR__ . '/../partials/pagination.php';
?>
```

### Tất cả options PHP

```php
echo renderPagination([
    'totalItems'      => 120,        // Tổng số bản ghi
    'limit'           => 10,         // Số bản ghi / trang
    'currentPage'     => 1,          // Trang hiện tại
    'maxVisiblePages' => 5,          // Số nút trang tối đa
    'pageParam'       => 'p',        // Tên param URL (?p=2)
    'baseUrl'         => '',         // URL cơ sở (để trống = auto)
    'showInfo'        => true,       // Hiện info bar
    'prevText'        => '&laquo; Trước',
    'nextText'        => 'Sau &raquo;',
    'cssPrefix'       => '',         // Prefix CSS class
    'preserveQuery'   => true,       // Giữ query params khác
]);
```

---

## 🔵 CÁCH 2: JavaScript Client-Side (AJAX)

### Ví dụ cơ bản

```html
<!-- Container cho pagination -->
<div id="my-pagination"></div>

<script>
const pager = new Pagination({
    container  : '#my-pagination',
    totalItems : 120,
    limit      : 10,
    currentPage: 1,
    onPageChange: function(page) {
        console.log('Chuyển đến trang:', page);
        loadData(page);  // Gọi API load data
    }
});
</script>
```

### Ví dụ AJAX thực tế

```html
<div id="movie-list"></div>
<div id="movie-pagination"></div>

<script>
let moviePager;

// Hàm load data từ API
async function loadMovies(page = 1) {
    const limit = 10;
    const response = await fetch(`/api/movies?page=${page}&limit=${limit}`);
    const data = await response.json();

    // Render danh sách phim
    document.getElementById('movie-list').innerHTML = data.movies
        .map(m => `<div class="movie-card">${m.title}</div>`)
        .join('');

    // Cập nhật pagination
    if (!moviePager) {
        moviePager = new Pagination({
            container   : '#movie-pagination',
            totalItems  : data.totalRecords,
            limit       : limit,
            currentPage : page,
            onPageChange: loadMovies
        });
    } else {
        moviePager.update({
            totalItems : data.totalRecords,
            currentPage: page
        });
    }
}

// Load trang đầu
loadMovies(1);
</script>
```

### Dùng hàm tắt `createPagination()`

```javascript
const pager = createPagination('#my-pagination', {
    totalItems: 100,
    limit: 10,
    onPageChange: (page) => loadData(page)
});
```

### Tất cả options JavaScript

```javascript
const pager = new Pagination({
    container      : '#pagination',    // (bắt buộc) CSS selector hoặc DOM element
    totalItems     : 0,                // Tổng số bản ghi
    limit          : 10,               // Số bản ghi / trang
    currentPage    : 1,                // Trang hiện tại
    maxVisiblePages: 5,                // Số nút trang tối đa
    mode           : 'client',         // 'client' (AJAX) hoặc 'server' (URL)
    onPageChange   : null,             // Callback(page) khi chuyển trang
    baseUrl        : '',               // URL cơ sở (mode server)
    pageParam      : 'p',             // Tên param URL
    showInfo       : true,             // Hiện info bar
    prevText       : '« Trước',
    nextText       : 'Sau »',
    infoFormat     : 'Hiển thị {from}-{to} / {total} bản ghi',
    pageInfoFormat : 'Trang {current} / {totalPages}',
    scrollToTop    : false,            // Cuộn lên khi chuyển trang
    cssPrefix      : '',               // Prefix CSS class
});
```

### Các method hữu ích

```javascript
// Chuyển đến trang cụ thể
pager.goToPage(5);

// Trang tiếp / trước
pager.nextPage();
pager.prevPage();

// Trang đầu / cuối
pager.firstPage();
pager.lastPage();

// Cập nhật options (render lại tự động)
pager.update({ totalItems: 200, currentPage: 3, limit: 20 });

// Lấy thông tin hiện tại
const info = pager.getInfo();
// => { currentPage: 3, totalPages: 10, totalItems: 200, limit: 20, from: 41, to: 60 }

// Lấy offset cho SQL query
const offset = pager.getOffset();
// => 40

// Hủy pagination
pager.destroy();
```

---

## 🎨 CSS Themes

### Dark Theme (mặc định)
Tự động áp dụng, phù hợp với admin panel.

### Light Theme
Thêm class `pagination-light` vào wrapper:

```html
<div class="pagination-light">
    <div id="my-pagination"></div>
</div>
```

---

## 📋 Checklist cho Dev mới

1. ✅ CSS `pagination.css` đã được include trong `head.php` (cả admin và user)
2. ✅ JS `pagination.js` đã được include trong `head.php` (cả admin và user)
3. ✅ PHP component ở `views/components/pagination.php`
4. ✅ Backward-compatible với code cũ qua `views/admin/partials/pagination.php`

### Khi tạo trang mới có phân trang:

**PHP:**
```php
require_once __DIR__ . '/../../components/pagination.php';
echo renderPagination(['totalItems' => $total, 'limit' => 10, 'currentPage' => $_GET['p'] ?? 1]);
```

**JS:**
```javascript
const pager = createPagination('#pagination-container', {
    totalItems: 100, limit: 10, onPageChange: (p) => loadData(p)
});
```

---

## ❓ FAQ

**Q: Có thể đặt nhiều pagination trên cùng 1 trang không?**  
A: Có! Mỗi cái dùng container riêng:
```javascript
const pager1 = createPagination('#pagination-1', { ... });
const pager2 = createPagination('#pagination-2', { ... });
```

**Q: Làm sao dùng với filter/search?**  
A: PHP tự động giữ lại các query params khác (option `preserveQuery: true`).  
JS: Truyền params trong callback `onPageChange`.

**Q: CSS bị xung đột với component khác?**  
A: Dùng option `cssPrefix` để thêm prefix cho tất cả class names.

