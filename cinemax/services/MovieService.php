<?php

class MovieService
{
    private $movieModel;
    private $genreModel;

    public function __construct($movieModel, $genreModel)
    {
        $this->movieModel = $movieModel;
        $this->genreModel = $genreModel;
    }

    public function getAllGenres()
    {
        return $this->genreModel->getAllGenres();
    }

    public function listMoviesAdmin($search = '', $genreId = null, $statusText = '')
    {
        $this->movieModel->syncStatuses();

        $statusMap = [
            '' => null,
            'Sắp chiếu' => 1,
            'Đang chiếu' => 2,
            'Ngừng chiếu' => 3,
            'Đã ẩn' => 0,
        ];

        if (!array_key_exists($statusText, $statusMap)) {
            throw new InvalidArgumentException("Trạng thái lọc không hợp lệ.");
        }

        if ($genreId !== null && $genreId !== '') {
            if (!is_numeric($genreId) || (int)$genreId <= 0) {
                throw new InvalidArgumentException("Genre ID không hợp lệ.");
            }
            $genreId = (int)$genreId;
        } else {
            $genreId = null;
        }

        return $this->movieModel->getMoviesForAdmin($search, $genreId, $statusMap[$statusText]);
    }

    public function getMovieById($movieId)
    {
        $this->movieModel->syncStatuses();

        if (!is_numeric($movieId) || (int)$movieId <= 0) {
            throw new InvalidArgumentException("Movie ID không hợp lệ.");
        }

        return $this->movieModel->getMovieById((int)$movieId);
    }

    public function createMovie($movieData, $genreIds = [])
    {
        $clean = $this->sanitizeMoviePayload($movieData, $genreIds);

        if (count($clean['genreIds']) > 0) {
            $cnt = $this->genreModel->countActiveByIds($clean['genreIds']);
            if ($cnt !== count($clean['genreIds'])) {
                throw new InvalidArgumentException("Có thể loại không tồn tại hoặc đã bị khóa.");
            }
        }

        $movieId = $this->movieModel->createMovieWithGenres($clean['data'], $clean['genreIds']);
        if (!$movieId) {
            throw new Exception("Không thể thêm phim.");
        }

        return $movieId;
    }

    public function updateMovie($movieId, $movieData, $genreIds = [])
    {
        if (!is_numeric($movieId) || (int)$movieId <= 0) {
            throw new InvalidArgumentException("Movie ID không hợp lệ.");
        }
        $movieId = (int)$movieId;
        $existing=$this->movieModel->getMovieById($movieId);
        if (!$existing) throw new RuntimeException('Phim không tồn tại.');
        if ((int)$existing['status']===0 && (int)($movieData['status']??0)!==0) throw new RuntimeException('Phim đang ẩn. Vui lòng xác nhận gia hạn bản quyền để kích hoạt lại.');

        $clean = $this->sanitizeMoviePayload($movieData, $genreIds);

        if (count($clean['genreIds']) > 0) {
            $cnt = $this->genreModel->countActiveByIds($clean['genreIds']);
            if ($cnt !== count($clean['genreIds'])) {
                throw new InvalidArgumentException("Có thể loại không tồn tại hoặc đã bị khóa.");
            }
        }

        $ok = $this->movieModel->updateMovieWithGenres($movieId, $clean['data'], $clean['genreIds']);
        if (!$ok) {
            throw new Exception("Không thể cập nhật phim.");
        }

        return true;
    }

    public function deleteMovie($movieId)
    {
        if (!is_numeric($movieId) || (int)$movieId <= 0) {
            throw new InvalidArgumentException("Movie ID không hợp lệ.");
        }

        if (!$this->movieModel->deleteMovie((int)$movieId)) {
            throw new Exception("Không thể ẩn phim.");
        }

        return true;
    }

    public function restoreMovie($movieId, bool $licenseRenewed): void
    {
        if (!$licenseRenewed) throw new RuntimeException('Vui lòng xác nhận đã mua hoặc gia hạn bản quyền trước khi kích hoạt phim.');
        if (!is_numeric($movieId) || (int)$movieId<=0) throw new InvalidArgumentException('Movie ID không hợp lệ.');
        if (!$this->movieModel->restoreMovie((int)$movieId)) throw new RuntimeException('Phim không tồn tại hoặc không ở trạng thái đã ẩn.');
    }

    public function listMoviesForUser($filter = 'all')
    {
        $this->movieModel->syncStatuses();

        $allowed = ['all', 'now-showing', 'coming-soon'];
        if (!in_array($filter, $allowed, true)) {
            $filter = 'all';
        }

        $status = null;
        if ($filter === 'now-showing') {
            $status = 2;
        } elseif ($filter === 'coming-soon') {
            $status = 1;
        }

        return $this->movieModel->getMoviesForUser($status);
    }

    public function searchMovies($keyword)
    {
        $this->movieModel->syncStatuses();

        $keyword = trim((string)$keyword);
        if ($keyword === '') {
            return [];
        }

        return $this->movieModel->searchMoviesForUser($keyword);
    }

    public function getMovieDetailForUser($movieId)
    {
        $this->movieModel->syncStatuses();

        if (!is_numeric($movieId) || (int)$movieId <= 0) {
            throw new InvalidArgumentException("Movie ID không hợp lệ.");
        }

        return $this->movieModel->getMovieById((int)$movieId);
    }

    private function sanitizeMoviePayload($movieData, $genreIds)
    {
        $title       = trim((string)($movieData['title'] ?? ''));
        $description = trim((string)($movieData['description'] ?? ''));
        $durationMin = $movieData['duration_min'] ?? null;
        $releaseDate = $movieData['release_date'] ?? null;
        $posterUrl   = $movieData['poster_url'] ?? null;
        $trailerUrl  = $movieData['trailer_url'] ?? null;
        $status      = $movieData['status'] ?? null;
        $director    = trim((string)($movieData['director'] ?? ''));
        $actors      = trim((string)($movieData['actors'] ?? ''));

        if ($title === '') {
            throw new InvalidArgumentException("Tên phim không được để trống.");
        }
        if (mb_strlen($title) > 255) {
            throw new InvalidArgumentException("Tên phim không được vượt quá 255 ký tự.");
        }

        if ($description === '') {
            $description = '';
        }

        if ($durationMin === null || !is_numeric($durationMin) || (int)$durationMin <= 0) {
            throw new InvalidArgumentException("Thời lượng phim không hợp lệ.");
        }
        $durationMin = (int)$durationMin;

        if ($status === null || !is_numeric($status)) {
            throw new InvalidArgumentException("Trạng thái phim không hợp lệ.");
        }
        $status = (int)$status;
        if (!in_array($status, [0, 1, 2, 3], true)) {
            throw new InvalidArgumentException("Trạng thái phim không hợp lệ.");
        }

        $releaseDate = $releaseDate !== null ? trim((string)$releaseDate) : '';
        if ($releaseDate === '') {
            throw new InvalidArgumentException("Ngày chiếu không được để trống.");
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $releaseDate)) {
            throw new InvalidArgumentException("Ngày chiếu phải có định dạng YYYY-MM-DD.");
        }

        $releaseDateObject = DateTime::createFromFormat('Y-m-d', $releaseDate);
        if (!$releaseDateObject || $releaseDateObject->format('Y-m-d') !== $releaseDate) {
            throw new InvalidArgumentException("Ngày chiếu không hợp lệ.");
        }

        if (in_array($status, [1, 2], true)) {
            $status = $releaseDate > date('Y-m-d') ? 1 : 2;
        }

        $posterUrl = $posterUrl !== null ? trim((string)$posterUrl) : null;
        if ($posterUrl === '') {
            $posterUrl = null;
        }

        $trailerUrl = $trailerUrl !== null ? trim((string)$trailerUrl) : null;
        if ($trailerUrl === '') {
            $trailerUrl = null;
        }

        if (mb_strlen($director) > 255) {
            throw new InvalidArgumentException("Tên đạo diễn quá dài.");
        }

        $normalizedGenreIds = [];
        if (is_array($genreIds)) {
            foreach ($genreIds as $gid) {
                if (!is_numeric($gid)) continue;
                $gid = (int)$gid;
                if ($gid > 0) {
                    $normalizedGenreIds[$gid] = true;
                }
            }
        }
        $normalizedGenreIds = array_keys($normalizedGenreIds);

        return [
            'data' => [
                'title'        => $title,
                'description'  => $description,
                'duration_min' => $durationMin,
                'release_date' => $releaseDate,
                'poster_url'   => $posterUrl,
                'trailer_url'  => $trailerUrl,
                'status'       => $status,
                'director'     => $director,
                'actors'       => $actors,
            ],
            'genreIds' => $normalizedGenreIds
        ];
    }
}
