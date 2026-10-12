<?php
$filter = $_GET['filter'] ?? 'all';

if (!isset($movieService)) {
    die('MovieService chưa được khởi tạo.');
}

$movies = $movieService->listMoviesForUser($filter);

function movieStatusText($status)
{
    return match ((int)$status) {
        1 => 'Sắp chiếu',
        2 => 'Đang chiếu',
        3 => 'Ngừng chiếu',
        default => 'Không xác định',
    };
}

function movieStatusClass($status)
{
    return (int)$status === 2 ? 'tag-primary' : '';
}

function buildPosterUrl($posterUrl)
{
    $posterUrl = trim((string)$posterUrl);

    if ($posterUrl === '') {
        return '/cinemax/cinemax/public/assets/uploads/movies/no-image.png';
    }

    if (preg_match('/^https?:\/\//i', $posterUrl)) {
        return $posterUrl;
    }

    return '/cinemax/cinemax/public/' . ltrim($posterUrl, '/');
}
?>

<section class="movies">
    <main class="section">
        <div class="container">
            <div class="section-header">
                <h2>Tất cả phim</h2>
                <div class="filter-tabs">
                    <a href="index.php?page=movies&filter=all" class="<?= $filter === 'all' ? 'active' : '' ?> btn-filter">
                        Tất cả
                    </a>
                    <a href="index.php?page=movies&filter=now-showing" class="<?= $filter === 'now-showing' ? 'active' : '' ?> btn-filter">
                        Đang chiếu
                    </a>
                    <a href="index.php?page=movies&filter=coming-soon" class="<?= $filter === 'coming-soon' ? 'active' : '' ?> btn-filter">
                        Sắp chiếu
                    </a>
                </div>
            </div>

            <div class="movie-grid">
                <?php if (empty($movies)): ?>
                    <div style="grid-column: 1 / -1; text-align:center; padding: 32px;">
                        <p>Hiện chưa có phim phù hợp.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($movies as $movie): ?>
                        <div class="movie-card">
                            <div class="movie-poster">
                                <img
                                    src="<?= htmlspecialchars(buildPosterUrl($movie['poster_url'] ?? '')) ?>"
                                    alt="<?= htmlspecialchars($movie['title'] ?? 'Tên phim') ?>"
                                    onerror="this.src='/cinemax/cinemax/public/assets/uploads/movies/no-image.png'">

                                <div class="movie-overlay">
                                    <a href="index.php?page=movie_detail&id=<?= (int)$movie['movie_id'] ?>" class="overlay-btn btn-detail">
                                        Chi tiết
                                    </a>
                                </div>
                            </div>

                            <div class="movie-info">
                                <h3><?= htmlspecialchars($movie['title'] ?? 'Tên phim') ?></h3>

                                <div class="movie-meta">
                                    <span class="duration"><?= (int)($movie['duration_min'] ?? 0) ?> phút</span>
                                </div>

                                <div style="margin: 10px 0;">
                                    <span class="tag <?= movieStatusClass($movie['status'] ?? 0) ?>">
                                        <?= htmlspecialchars(movieStatusText($movie['status'] ?? 0)) ?>
                                    </span>
                                </div>

                                <?php if ((int)($movie['status'] ?? 0) === 2): ?>
                                    <a href="index.php?page=movie_detail&id=<?= (int)$movie['movie_id'] ?>#booking" class="btn-book-ticket">
                                        Đặt vé
                                    </a>
                                <?php else: ?>
                                    <button class="btn-book-ticket" disabled style="background: var(--bg-tertiary); cursor: not-allowed;">
                                        Chưa mở bán
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</section>
