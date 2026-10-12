<?php
/**
 * ============================================
 *  PAGINATION COMPONENT (PHP) - Phân trang tái sử dụng
 * ============================================
 *
 *  CÁCH DÙNG:
 *  ──────────
 *
 *  Cách 1: Dùng biến (truyền trước khi include)
 *  ──────────────────────────────────────────────
 *    $paginationConfig = [
 *        'totalItems'  => 120,
 *        'limit'       => 10,
 *        'currentPage' => $_GET['p'] ?? 1,
 *    ];
 *    include __DIR__ . '/../partials/pagination.php';
 *
 *
 *  Cách 2: Dùng hàm renderPagination() (khuyên dùng)
 *  ──────────────────────────────────────────────────
 *    require_once __DIR__ . '/../partials/pagination.php';
 *    echo renderPagination([
 *        'totalItems'  => $totalRecords,
 *        'limit'       => 10,
 *        'currentPage' => $_GET['p'] ?? 1,
 *    ]);
 *
 *
 *  OPTIONS:
 *  ────────
 *  | Option          | Type   | Default         | Mô tả                                   |
 *  |-----------------|--------|-----------------|-----------------------------------------|
 *  | totalItems      | int    | 0               | Tổng số bản ghi                         |
 *  | limit           | int    | 10              | Số bản ghi mỗi trang                    |
 *  | currentPage     | int    | 1               | Trang hiện tại                           |
 *  | maxVisiblePages | int    | 5               | Số nút trang tối đa hiển thị             |
 *  | pageParam       | string | 'p'             | Tên param trên URL                       |
 *  | baseUrl         | string | '' (auto)       | URL cơ sở (để trống = auto detect)       |
 *  | showInfo        | bool   | true            | Hiện info "Hiển thị x-y / z bản ghi"    |
 *  | prevText        | string | '« Trước'       | Text nút Trước                           |
 *  | nextText        | string | 'Sau »'         | Text nút Sau                             |
 *  | cssPrefix       | string | ''              | Prefix CSS class (tránh xung đột)        |
 *  | preserveQuery   | bool   | true            | Giữ lại các query params khác trên URL   |
 */

/**
 * Render HTML pagination
 *
 * @param array $config Cấu hình pagination
 * @return string HTML
 */
function renderPagination(array $config = []): string
{
    // Merge defaults
    $defaults = [
        'totalItems'      => 0,
        'limit'           => 10,
        'currentPage'     => 1,
        'maxVisiblePages' => 5,
        'pageParam'       => 'p',
        'baseUrl'         => '',
        'showInfo'        => true,
        'prevText'        => '&laquo; Trước',
        'nextText'        => 'Sau &raquo;',
        'cssPrefix'       => '',
        'preserveQuery'   => true,
    ];
    $cfg = array_merge($defaults, $config);

    $totalItems = (int) $cfg['totalItems'];
    $limit      = max(1, (int) $cfg['limit']);
    $totalPages = max(1, (int) ceil($totalItems / $limit));
    $currentPage = max(1, min((int) $cfg['currentPage'], $totalPages));
    $maxVis     = (int) $cfg['maxVisiblePages'];
    $pageParam  = $cfg['pageParam'];
    $pfx        = $cfg['cssPrefix'];

    // Không cần phân trang
    if ($totalItems <= 0 || $totalPages <= 1) {
        return '';
    }

    // Tính range trang hiển thị
    $half   = (int) floor($maxVis / 2);
    $startP = max(1, $currentPage - $half);
    $endP   = min($totalPages, $startP + $maxVis - 1);
    if ($endP - $startP + 1 < $maxVis) {
        $startP = max(1, $endP - $maxVis + 1);
    }

    // Build query string (giữ lại params khác)
    $queryParams = $cfg['preserveQuery'] ? $_GET : [];
    unset($queryParams[$pageParam]);

    // Hàm tạo URL nội bộ
    $buildUrl = function (int $page) use ($cfg, $queryParams, $pageParam) {
        $params = $queryParams;
        $params[$pageParam] = $page;
        $qs = http_build_query($params);

        if (!empty($cfg['baseUrl'])) {
            $sep = str_contains($cfg['baseUrl'], '?') ? '&' : '?';
            return $cfg['baseUrl'] . $sep . $qs;
        }
        return '?' . $qs;
    };

    // Tính from/to
    $from = ($currentPage - 1) * $limit + 1;
    $to   = min($currentPage * $limit, $totalItems);

    // ─── BẮT ĐẦU RENDER HTML ───
    $html = '';

    // Info bar
    if ($cfg['showInfo']) {
        $html .= "<div class=\"{$pfx}pagination-info\">";
        $html .= "<span>Hiển thị {$from}-{$to} / {$totalItems} bản ghi</span>";
        $html .= "<span>Trang {$currentPage} / {$totalPages}</span>";
        $html .= "</div>";
    }

    $html .= "<div class=\"{$pfx}pagination\">";

    // Nút Trước
    if ($currentPage > 1) {
        $html .= "<a href=\"" . htmlspecialchars($buildUrl($currentPage - 1)) . "\" class=\"{$pfx}page-link {$pfx}prev\">{$cfg['prevText']}</a>";
    } else {
        $html .= "<span class=\"{$pfx}page-link {$pfx}prev {$pfx}disabled\">{$cfg['prevText']}</span>";
    }

    // Trang đầu + dots
    if ($startP > 1) {
        $html .= "<a href=\"" . htmlspecialchars($buildUrl(1)) . "\" class=\"{$pfx}page-link\">1</a>";
        if ($startP > 2) {
            $html .= "<span class=\"{$pfx}page-dots\">...</span>";
        }
    }

    // Các nút trang
    for ($i = $startP; $i <= $endP; $i++) {
        if ($i === $currentPage) {
            $html .= "<span class=\"{$pfx}page-link {$pfx}active\">{$i}</span>";
        } else {
            $html .= "<a href=\"" . htmlspecialchars($buildUrl($i)) . "\" class=\"{$pfx}page-link\">{$i}</a>";
        }
    }

    // Dots + trang cuối
    if ($endP < $totalPages) {
        if ($endP < $totalPages - 1) {
            $html .= "<span class=\"{$pfx}page-dots\">...</span>";
        }
        $html .= "<a href=\"" . htmlspecialchars($buildUrl($totalPages)) . "\" class=\"{$pfx}page-link\">{$totalPages}</a>";
    }

    // Nút Sau
    if ($currentPage < $totalPages) {
        $html .= "<a href=\"" . htmlspecialchars($buildUrl($currentPage + 1)) . "\" class=\"{$pfx}page-link {$pfx}next\">{$cfg['nextText']}</a>";
    } else {
        $html .= "<span class=\"{$pfx}page-link {$pfx}next {$pfx}disabled\">{$cfg['nextText']}</span>";
    }

    $html .= "</div>";

    return $html;
}

// ──────────────────────────────────────────────
// Nếu include trực tiếp (tương thích cách cũ)
// ──────────────────────────────────────────────
if (isset($paginationConfig) || isset($totalPages)) {
    // Cách mới: dùng $paginationConfig
    if (isset($paginationConfig) && is_array($paginationConfig)) {
        echo renderPagination($paginationConfig);
    }
    // Cách cũ: dùng biến rời $totalPages, $currentPage, $totalItems, $limit
    elseif (isset($totalPages)) {
        echo renderPagination([
            'totalItems'  => $totalItems ?? 0,
            'limit'       => $limit ?? 10,
            'currentPage' => $currentPage ?? 1,
        ]);
    }
}

