<?php
$query = trim((string)($_GET['keyword'] ?? $_GET['q'] ?? $_GET['search'] ?? $_GET['query'] ?? ''));
$movies = [];

if (!isset($movieService)) {
    die('MovieService chưa được khởi tạo.');
}

if ($query !== '') {
    $movies = $movieService->searchMovies($query);
}

function searchMovieStatusText($status)
{
    return match ((int)$status) {
        1 => 'Sắp chiếu',
        2 => 'Đang chiếu',
        3 => 'Ngừng chiếu',
        default => 'Không xác định',
    };
}

function searchMovieStatusClass($status)
{
    return (int)$status === 2 ? 'tag-primary' : '';
}

function buildSearchPosterUrl($posterUrl)
{
    $posterUrl = trim((string)$posterUrl);

    if ($posterUrl === '') {
        return '/web_cinemax/public/assets/uploads/movies/no-image.png';
    }

    if (preg_match('/^https?:\/\//i', $posterUrl)) {
        return $posterUrl;
    }

    $posterUrl = ltrim($posterUrl, '/');
    if (strpos($posterUrl, 'public/') === 0) {
        $posterUrl = substr($posterUrl, 7);
    }

    return '/web_cinemax/public/' . $posterUrl;
}
?>

<section class="movies">
    <main class="section">
        <div class="container">
            <div class="section-header">
                <h2>Kết quả tìm kiếm</h2>
                <?php if ($query !== ''): ?>
                    <p>Từ khóa: <strong><?= htmlspecialchars($query) ?></strong></p>
                <?php endif; ?>
            </div>

            <?php if ($query === ''): ?>
                <div style="text-align:center; padding: 32px;">
                    <p>Vui lòng nhập từ khóa tìm kiếm.</p>
                </div>
            <?php elseif (empty($movies)): ?>
                <div style="text-align:center; padding: 32px;">
                    <p>Không tìm thấy phim phù hợp.</p>
                </div>
            <?php else: ?>
                <div class="movie-grid">
                    <?php foreach ($movies as $movie): ?>
                        <div class="movie-card">
                            <div class="movie-poster">
                                <img
                                    src="<?= htmlspecialchars(buildSearchPosterUrl($movie['poster_url'] ?? '')) ?>"
                                    alt="<?= htmlspecialchars($movie['title'] ?? $movie['name'] ?? 'Tên phim') ?>"
                                    onerror="this.src='/web_cinemax/public/assets/uploads/movies/no-image.png'">

                                <div class="movie-overlay">
                                    <a href="index.php?page=movie_detail&id=<?= (int)$movie['movie_id'] ?>" class="overlay-btn btn-detail">
                                        Chi tiết
                                    </a>
                                </div>
                            </div>

                            <div class="movie-info">
                                <h3><?= htmlspecialchars($movie['title'] ?? $movie['name'] ?? 'Tên phim') ?></h3>

                                <div class="movie-meta">
                                    <span class="duration"><?= (int)($movie['duration_min'] ?? 0) ?> phút</span>
                                </div>

                                <div style="margin: 10px 0;">
                                    <span class="tag <?= searchMovieStatusClass($movie['status'] ?? 0) ?>">
                                        <?= htmlspecialchars(searchMovieStatusText($movie['status'] ?? 0)) ?>
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
                </div>
            <?php endif; ?>
        </div>
    </main>
</section>
