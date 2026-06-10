<?php
/**
 * Backward-compatible wrapper.
 * Delegates to views/components/pagination.php
 *
 * Cách cũ vẫn hoạt động:
 *   $totalPages, $currentPage, $totalItems, $limit  → include 'pagination.php';
 *
 * Cách mới (khuyên dùng):
 *   require_once __DIR__ . '/../../views/components/pagination.php';
 *   echo renderPagination([ ... ]);
 */

require_once __DIR__ . '/../../components/pagination.php';

// Nếu include theo cách cũ bằng biến rời
if (isset($totalPages) && !isset($paginationConfig)) {
    echo renderPagination([
        'totalItems'  => $totalItems ?? 0,
        'limit'       => $limit ?? 10,
        'currentPage' => $currentPage ?? 1,
    ]);
}
// Nếu truyền $paginationConfig
elseif (isset($paginationConfig) && is_array($paginationConfig)) {
    echo renderPagination($paginationConfig);
}
