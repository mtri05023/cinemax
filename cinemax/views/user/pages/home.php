<?php
$allPromotions = isset($promotionService) ? $promotionService->listPromotions() : [];

// Chỉ lấy khuyến mãi đang áp dụng
$activePromotions = array_values(array_filter($allPromotions, function ($promo) {
    return ($promo['computed_status'] ?? '') === 'active';
}));

if (!isset($movieService)) {
    die('MovieService chưa được khởi tạo.');
}

$nowShowingMovies = $movieService->listMoviesForUser('now-showing');
$comingSoonMovies = $movieService->listMoviesForUser('coming-soon');

function buildPosterUrlHome($posterUrl)
{
    $posterUrl = trim((string)$posterUrl);

    if ($posterUrl === '') {
        return '/cinemax/cinemax/public/assets/uploads/movies/no-image.png';
    }

    if (preg_match('/^https?:\/\//i', $posterUrl)) {
        return $posterUrl;
    }

    $posterUrl = ltrim($posterUrl, '/');

    if (strpos($posterUrl, 'public/') === 0) {
        $posterUrl = substr($posterUrl, 7);
    }

    return '/cinemax/cinemax/public/' . $posterUrl;
}

function formatPromotionDateVNHome($date)
{
    if (!$date) return 'Chưa cập nhật';
    return date('d/m/Y', strtotime($date));
}

function getPromotionBadgeTextHome($promo)
{
    $type = $promo['discount_type'] ?? 'percent';
    $value = (float)($promo['discount_value'] ?? 0);

    if ($type === 'percent') {
        return 'Giảm ' . rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') . '%';
    }

    return 'Giảm ' . number_format($value, 0, ',', '.') . 'đ';
}
?>

<section class="home hero" aria-label="Nội dung nổi bật">
    <div class="hero-slider">
        <article class="hero-slide active"
            style="--hero-image: url('https://images.pexels.com/photos/7991579/pexels-photo-7991579.jpeg?auto=compress&cs=tinysrgb&w=1920');"
            aria-hidden="false">
            <div class="container hero-slide-container">
                <div class="hero-content">
                    <h1>Trải nghiệm điện ảnh đỉnh cao</h1>
                    <p>Đặt vé online nhanh chóng - Nhận ưu đãi hấp dẫn</p>
                    <div class="hero-buttons">
                        <a href="#now-showing" class="btn-hero-primary">Đặt vé ngay</a>
                        <a href="index.php?page=movies" class="btn-hero-secondary">Xem tất cả phim</a>
                    </div>
                </div>
            </div>
        </article>

        <article class="hero-slide"
            style="--hero-image: url('https://images.pexels.com/photos/7991378/pexels-photo-7991378.jpeg?auto=compress&cs=tinysrgb&w=1920');"
            aria-hidden="true">
            <div class="container hero-slide-container">
                <div class="hero-content">
                    <h2>Thế giới điện ảnh trong tầm tay</h2>
                    <p>Khám phá những bom tấn mới nhất với chất lượng hình ảnh và âm thanh sống động.</p>
                    <div class="hero-buttons">
                        <a href="index.php?page=movies&filter=now-showing" class="btn-hero-primary">Khám phá ngay</a>
                        <a href="index.php?page=showtimes" class="btn-hero-secondary">Xem lịch chiếu</a>
                    </div>
                </div>
            </div>
        </article>

        <article class="hero-slide"
            style="--hero-image: url('https://images.pexels.com/photos/8263324/pexels-photo-8263324.jpeg?auto=compress&cs=tinysrgb&w=1920');"
            aria-hidden="true">
            <div class="container hero-slide-container">
                <div class="hero-content">
                    <h2>Tận hưởng nhiều hơn, chi tiêu ít hơn</h2>
                    <p>Săn mã giảm giá và các chương trình ưu đãi hấp dẫn dành riêng cho bạn.</p>
                    <div class="hero-buttons">
                        <a href="index.php?page=promotions" class="btn-hero-primary">Xem ưu đãi</a>
                        <a href="index.php?page=movies" class="btn-hero-secondary">Chọn phim ngay</a>
                    </div>
                </div>
            </div>
        </article>
    </div>

    <div class="hero-dots" role="tablist" aria-label="Chọn banner">
        <button class="dot active" type="button" role="tab" aria-label="Banner 1" aria-selected="true"></button>
        <button class="dot" type="button" role="tab" aria-label="Banner 2" aria-selected="false"></button>
        <button class="dot" type="button" role="tab" aria-label="Banner 3" aria-selected="false"></button>
    </div>
</section>

<section class="quick-booking"></section>

<section id="now-showing" class="section">
    <div class="container">
        <div class="section-header">
            <h2>Phim đang chiếu</h2>
            <a href="index.php?page=movies&filter=now-showing" class="view-all">
                Xem tất cả
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <path d="m9 18 6-6-6-6"></path>
                </svg>
            </a>
        </div>

        <div class="movie-grid" id="nowShowingMovies">
            <?php if (empty($nowShowingMovies)): ?>
                <p>Hiện chưa có phim đang chiếu.</p>
            <?php else: ?>
                <?php foreach (array_slice($nowShowingMovies, 0, 8) as $movie): ?>
                    <div class="movie-card">
                        <div class="movie-poster">
                            <img
                                src="<?= htmlspecialchars(buildPosterUrlHome($movie['poster_url'] ?? '')) ?>"
                                alt="<?= htmlspecialchars($movie['title'] ?? 'Movie title') ?>"
                                onerror="this.src='/cinemax/cinemax/public/assets/uploads/movies/no-image.png'">

                            <div class="movie-overlay">
                                <a href="index.php?page=movie_detail&id=<?= (int)$movie['movie_id'] ?>" class="overlay-btn btn-detail">
                                    Chi tiết
                                </a>
                                <a href="index.php?page=movie_detail&id=<?= (int)$movie['movie_id'] ?>#booking" class="overlay-btn btn-buy-overlay">
                                    Đặt vé
                                </a>
                            </div>
                        </div>

                        <div class="movie-info">
                            <h3><?= htmlspecialchars($movie['title'] ?? 'Tên phim') ?></h3>
                            <div class="movie-meta">
                                <span class="duration"><?= (int)($movie['duration_min'] ?? 0) ?> phút</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section bg-light">
    <div class="container">
        <div class="section-header">
            <h2>Phim sắp chiếu</h2>
            <a href="index.php?page=movies&filter=coming-soon" class="view-all">
                Xem tất cả
            </a>
        </div>

        <div class="movie-grid" id="comingSoonMovies">
            <?php if (empty($comingSoonMovies)): ?>
                <p>Hiện chưa có phim sắp chiếu.</p>
            <?php else: ?>
                <?php foreach (array_slice($comingSoonMovies, 0, 8) as $movie): ?>
                    <div class="movie-card">
                        <div class="movie-poster">
                            <img
                                src="<?= htmlspecialchars(buildPosterUrlHome($movie['poster_url'] ?? '')) ?>"
                                alt="<?= htmlspecialchars($movie['title'] ?? 'Movie title') ?>"
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
                            <button class="btn-book-ticket" disabled
                                style="background: var(--bg-tertiary); cursor: not-allowed; opacity: 0.7;">
                                Chưa mở bán
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>Khuyến mãi hot</h2>
            <a href="index.php?page=promotions" class="view-all">Xem tất cả</a>
        </div>

        <div class="promo-grid">
            <?php if (empty($activePromotions)): ?>
                <p>Hiện chưa có khuyến mãi hot.</p>
            <?php else: ?>
                <?php foreach (array_slice($activePromotions, 0, 4) as $promo): ?>
                    <div class="promo-card">
                        <div class="promo-visual" aria-hidden="true">
                            <div class="promo-ticket-icon">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                            <span class="promo-visual-label">Cinemax ưu đãi</span>
                            <strong><?= htmlspecialchars(getPromotionBadgeTextHome($promo)) ?></strong>
                            <span class="promo-visual-code">
                                <?= htmlspecialchars($promo['code'] ?? 'CINEMAX') ?>
                            </span>
                        </div>

                        <div class="promo-content">
                            <span class="promo-badge">
                                <?= htmlspecialchars(getPromotionBadgeTextHome($promo)) ?>
                            </span>

                            <h3><?= htmlspecialchars($promo['name'] ?? 'Ưu đãi Cinemax') ?></h3>

                            <div class="promo-code-row">
                                <span>Mã ưu đãi</span>
                                <strong><?= htmlspecialchars($promo['code'] ?? '') ?></strong>
                            </div>

                            <p class="promo-expiry">
                                <i class="far fa-clock"></i>
                                Hạn dùng: <?= htmlspecialchars(formatPromotionDateVNHome($promo['end_date'] ?? null)) ?>
                            </p>

                            <?php if ((float)($promo['min_amount'] ?? 0) > 0): ?>
                                <p class="promo-condition">
                                    Áp dụng cho đơn từ
                                    <?= number_format((float)$promo['min_amount'], 0, ',', '.') ?>đ
                                </p>
                            <?php endif; ?>

                            <?php if ((int)($promo['usage_limit'] ?? 0) > 0): ?>
                                <p class="promo-condition">
                                    Còn
                                    <?= max(0, (int)$promo['usage_limit'] - (int)($promo['usage_count'] ?? 0)) ?>
                                    lượt sử dụng
                                </p>
                            <?php endif; ?>

                            <a href="index.php?page=promotions" class="promo-link">
                                Xem chi tiết
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
