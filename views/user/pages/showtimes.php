<?php
$movie = null;
$movieId = $_GET['movie_id'] ?? ($_GET['id'] ?? null);

if ($movieId !== null && ctype_digit((string)$movieId)) {
    $movie = $movieService->getMovieDetailForUser((int)$movieId);
}

function buildPosterUrl($posterUrl)
{
    $posterUrl = trim((string)$posterUrl);

    if ($posterUrl === '') {
        return "data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='300' height='450' viewBox='0 0 300 450'><rect width='300' height='450' fill='%23111111'/><rect x='15' y='15' width='270' height='420' rx='12' fill='%231b1b1b' stroke='%23333333'/><text x='50%25' y='48%25' fill='%23999999' font-size='22' font-family='Arial, sans-serif' text-anchor='middle'>No Image</text><text x='50%25' y='55%25' fill='%23666666' font-size='14' font-family='Arial, sans-serif' text-anchor='middle'>Cinemax</text></svg>";
    }

    if (preg_match('/^https?:\/\//i', $posterUrl) || strpos($posterUrl, 'data:image/') === 0) {
        return $posterUrl;
    }

    if (strpos($posterUrl, '/web_cinemax/') === 0) {
        return $posterUrl;
    }

    $posterUrl = ltrim($posterUrl, '/');
    if (strpos($posterUrl, 'public/') === 0) {
        return '/web_cinemax/' . $posterUrl;
    }
    if (strpos($posterUrl, 'assets/') === 0) {
        return '/web_cinemax/public/' . $posterUrl;
    }
    if (strpos($posterUrl, 'uploads/') === 0) {
        return '/web_cinemax/public/assets/' . $posterUrl;
    }

    return '/web_cinemax/public/assets/uploads/movies/' . $posterUrl;
}

$posterUrl = buildPosterUrl($movie['poster_url'] ?? '');

$duration = !empty($movie['duration_min']) ? (string)$movie['duration_min'] : '';
$director = !empty($movie['director']) ? (string)$movie['director'] : 'Lý Hải';
$actors = !empty($movie['actors'])
    ? (string)$movie['actors']
    : 'Thanh Hiền, Trương Minh Cường, Đinh Y Nhung, Quách Ngọc Tuyên';
$genreNames = !empty($movie['genres'])
    ? implode(', ', array_column($movie['genres'], 'name'))
    : 'Tâm lý';
$description = !empty($movie['description'])
    ? (string)$movie['description']
    : 'Câu chuyện gia đình cảm động xoay quanh tình mẫu tử, những mâu thuẫn đời thường và hành trình hàn gắn giữa các thành viên.';
$movieTitle = !empty($movie['title']) ? (string)$movie['title'] : 'Lịch chiếu';
?>


<section class="showtimes">


    <div class="movie-header-bg">
        <div class="container">
            <div class="movie-header-content">
                <img id="showtimesPoster" src="<?= htmlspecialchars($posterUrl) ?>" class="header-poster">
                <div class="movie-header-info">
                    <h1 id="showtimesMovieTitle"><?= htmlspecialchars($movieTitle) ?></h1>
                    <div id="showtimesMovieMeta">
                        <?php if ($duration !== ''): ?>
                            <p>
                            Thời lượng:
                            <span id="showtimesDuration">
                                <?= htmlspecialchars($duration) ?> phút
                            </span>
                            </p>
                        <?php else: ?>
                            <span id="showtimesDuration" hidden></span>
                        <?php endif; ?>
                        <p>Đạo diễn: <span id="showtimesDirector"><?= htmlspecialchars($director) ?></span></p>
                        <p>Diễn viên: <span id="showtimesActors"><?= htmlspecialchars($actors) ?></span></p>
                        <p>Thể loại: <span id="showtimesGenres"><?= htmlspecialchars($genreNames) ?></span></p>
                        <div class="showtimes-description">
                            <h3>Nội dung phim</h3>
                            <p id="showtimesDescription"><?= htmlspecialchars($description) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <main class="section">
        <div class="container" id="showtimesContent">
            <div style="text-align: center; padding: 40px; color: #888;">
                <div class="loading">Đang tải lịch chiếu...</div>
            </div>
        </div>
    </main>

</section>

<script src="/web_cinemax/public/assets/js/api.js"></script>
<script src="/web_cinemax/public/assets/js/user-showtimes.js?v=20260611-6"></script>
