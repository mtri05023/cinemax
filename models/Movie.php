<?php

class Movie
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getMoviesForAdmin($search = '', $genreId = null, $status = null)
    {
        $search = trim((string)$search);

        $hasDirector = $this->columnExists('movies', 'director');
        $hasActors   = $this->columnExists('movies', 'actors');

        $extraCols = '';
        if ($hasDirector) $extraCols .= ', m.director';
        if ($hasActors)   $extraCols .= ', m.actors';

        $sql = "
            SELECT
                m.* {$extraCols},
                GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') AS genre_names,
                GROUP_CONCAT(DISTINCT g.genre_id ORDER BY g.genre_id SEPARATOR ',') AS genre_ids
            FROM movies m
            LEFT JOIN movie_genres mg ON mg.movie_id = m.movie_id
            LEFT JOIN genres g ON g.genre_id = mg.genre_id AND g.status = 1
            WHERE m.status IN (1, 2, 3)
        ";

        $types = "";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (m.title LIKE ? OR m.description LIKE ?)";
            $like = '%' . $search . '%';
            $types .= "ss";
            $params[] = $like;
            $params[] = $like;
        }

        // reset = hiện tất cả 1,2,3
        if ($status !== null) {
            $sql .= " AND m.status = ?";
            $types .= "i";
            $params[] = (int)$status;
        }

        if ($genreId !== null) {
            $sql .= " AND EXISTS (
                SELECT 1
                FROM movie_genres mg2
                WHERE mg2.movie_id = m.movie_id
                  AND mg2.genre_id = ?
            )";
            $types .= "i";
            $params[] = (int)$genreId;
        }

        $sql .= " GROUP BY m.movie_id ORDER BY m.movie_id DESC";

        $stmt = $this->conn->prepare($sql);
        if ($types !== "") {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();

        $result = $stmt->get_result();
        $movies = [];
        while ($row = $result->fetch_assoc()) {
            $movies[] = $row;
        }

        return $movies;
    }

    public function getMovieById($movieId)
    {
        $hasDirector = $this->columnExists('movies', 'director');
        $hasActors   = $this->columnExists('movies', 'actors');

        $extraCols = '';
        if ($hasDirector) $extraCols .= ', director';
        if ($hasActors)   $extraCols .= ', actors';

        $sql = "SELECT movie_id, title, description, duration_min, release_date, poster_url, trailer_url, status {$extraCols}
                FROM movies
                WHERE movie_id = ?
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $movieId);
        $stmt->execute();

        $movie = $stmt->get_result()->fetch_assoc();
        if (!$movie) return null;

        $sqlG = "SELECT g.genre_id, g.name
                 FROM movie_genres mg
                 JOIN genres g ON g.genre_id = mg.genre_id
                 WHERE mg.movie_id = ? AND g.status = 1
                 ORDER BY g.name ASC";

        $stmtG = $this->conn->prepare($sqlG);
        $stmtG->bind_param("i", $movieId);
        $stmtG->execute();

        $genres = [];
        $rsG = $stmtG->get_result();
        while ($row = $rsG->fetch_assoc()) {
            $genres[] = $row;
        }

        $movie['genres'] = $genres;
        return $movie;
    }

    public function syncStatuses(): void
    {
        $this->conn->query("
            UPDATE movies
            SET status = 1
            WHERE status IN (1, 2)
              AND release_date > CURDATE()
        ");

        $this->conn->query("
            UPDATE movies
            SET status = 2
            WHERE status IN (1, 2)
              AND release_date <= CURDATE()
        ");
    }

    public function createMovieWithGenres(array $data, array $genreIds)
    {
        $this->conn->begin_transaction();

        try {
            $hasDirector = $this->columnExists('movies', 'director');
            $hasActors   = $this->columnExists('movies', 'actors');

            $columns = "title, description, duration_min, release_date, poster_url, trailer_url, status, created_at";
            $values  = "?, ?, ?, ?, ?, ?, ?, NOW()";
            $types   = "ssisssi";

            $params = [
                $data['title'],
                $data['description'],
                $data['duration_min'],
                $data['release_date'],
                $data['poster_url'],
                $data['trailer_url'],
                $data['status'],
            ];

            if ($hasDirector) {
                $columns .= ", director";
                $values  .= ", ?";
                $types   .= "s";
                $params[] = $data['director'] ?? '';
            }

            if ($hasActors) {
                $columns .= ", actors";
                $values  .= ", ?";
                $types   .= "s";
                $params[] = $data['actors'] ?? '';
            }

            $sql = "INSERT INTO movies ($columns) VALUES ($values)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);

            if (!$stmt->execute()) {
                throw new Exception("Insert movies failed.");
            }

            $movieId = (int)$this->conn->insert_id;

            if (count($genreIds) > 0) {
                $stmtMG = $this->conn->prepare("INSERT INTO movie_genres (movie_id, genre_id) VALUES (?, ?)");
                foreach ($genreIds as $gid) {
                    $gid = (int)$gid;
                    $stmtMG->bind_param("ii", $movieId, $gid);
                    if (!$stmtMG->execute()) {
                        throw new Exception("Insert movie_genres failed.");
                    }
                }
            }

            $this->conn->commit();
            return $movieId;

        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }

    public function updateMovieWithGenres(int $movieId, array $data, array $genreIds)
    {
        $this->conn->begin_transaction();

        try {
            $hasDirector = $this->columnExists('movies', 'director');
            $hasActors   = $this->columnExists('movies', 'actors');

            $set   = "title = ?, description = ?, duration_min = ?, release_date = ?, poster_url = ?, trailer_url = ?, status = ?";
            $types = "ssisssi";

            $params = [
                $data['title'],
                $data['description'],
                $data['duration_min'],
                $data['release_date'],
                $data['poster_url'],
                $data['trailer_url'],
                $data['status'],
            ];

            if ($hasDirector) {
                $set .= ", director = ?";
                $types .= "s";
                $params[] = $data['director'] ?? '';
            }

            if ($hasActors) {
                $set .= ", actors = ?";
                $types .= "s";
                $params[] = $data['actors'] ?? '';
            }

            $types .= "i";
            $params[] = $movieId;

            $sql = "UPDATE movies SET $set WHERE movie_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);

            if (!$stmt->execute()) {
                throw new Exception("Update movies failed.");
            }

            $stmtDel = $this->conn->prepare("DELETE FROM movie_genres WHERE movie_id = ?");
            $stmtDel->bind_param("i", $movieId);
            if (!$stmtDel->execute()) {
                throw new Exception("Delete old movie_genres failed.");
            }

            if (count($genreIds) > 0) {
                $stmtMG = $this->conn->prepare("INSERT INTO movie_genres (movie_id, genre_id) VALUES (?, ?)");
                foreach ($genreIds as $gid) {
                    $gid = (int)$gid;
                    $stmtMG->bind_param("ii", $movieId, $gid);
                    if (!$stmtMG->execute()) {
                        throw new Exception("Insert movie_genres failed.");
                    }
                }
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }

    public function deleteMovie(int $movieId)
    {
        $this->conn->begin_transaction();

        try {
            $stmtCheck = $this->conn->prepare("SELECT COUNT(*) AS cnt FROM shows WHERE movie_id = ?");
            $stmtCheck->bind_param("i", $movieId);
            if (!$stmtCheck->execute()) {
                throw new Exception("Check movie shows failed.");
            }

            $row = $stmtCheck->get_result()->fetch_assoc();
            $showCount = (int)($row['cnt'] ?? 0);

            if ($showCount > 0) {
                $stmtHide = $this->conn->prepare("UPDATE movies SET status = 0 WHERE movie_id = ?");
                $stmtHide->bind_param("i", $movieId);
                if (!$stmtHide->execute()) {
                    throw new Exception("Hide movie failed.");
                }

                $this->conn->commit();
                return true;
            }

            $stmt1 = $this->conn->prepare("DELETE FROM movie_genres WHERE movie_id = ?");
            $stmt1->bind_param("i", $movieId);
            if (!$stmt1->execute()) {
                throw new Exception("Delete movie_genres failed.");
            }

            $stmt2 = $this->conn->prepare("DELETE FROM movies WHERE movie_id = ?");
            $stmt2->bind_param("i", $movieId);
            if (!$stmt2->execute()) {
                throw new Exception("Delete movie failed.");
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        $table = $this->conn->real_escape_string($table);
        $column = $this->conn->real_escape_string($column);

        $sql = "SHOW COLUMNS FROM `$table` LIKE '$column'";
        $rs = $this->conn->query($sql);

        return $rs && $rs->num_rows > 0;
    }
    public function getMoviesForUser($status = null)
{
    $hasDirector = $this->columnExists('movies', 'director');
    $hasActors   = $this->columnExists('movies', 'actors');

    $extraCols = '';
    if ($hasDirector) $extraCols .= ', m.director';
    if ($hasActors)   $extraCols .= ', m.actors';

    $sql = "
        SELECT
            m.* {$extraCols},
            GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') AS genre_names,
            GROUP_CONCAT(DISTINCT g.genre_id ORDER BY g.genre_id SEPARATOR ',') AS genre_ids
        FROM movies m
        LEFT JOIN movie_genres mg ON mg.movie_id = m.movie_id
        LEFT JOIN genres g ON g.genre_id = mg.genre_id AND g.status = 1
        WHERE m.status IN (1, 2)
    ";

    $types = "";
    $params = [];

    if ($status !== null) {
        $sql .= " AND m.status = ?";
        $types .= "i";
        $params[] = (int)$status;
    }

    $sql .= " GROUP BY m.movie_id ORDER BY 
              CASE WHEN m.status = 2 THEN 1 ELSE 2 END,
              m.release_date DESC,
              m.movie_id DESC";

    $stmt = $this->conn->prepare($sql);
    if ($types !== "") {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();

    $result = $stmt->get_result();
    $movies = [];
    while ($row = $result->fetch_assoc()) {
        $movies[] = $row;
    }

    return $movies;
}

    public function searchMoviesForUser(string $keyword): array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return [];
        }

        $hasDirector = $this->columnExists('movies', 'director');
        $hasActors   = $this->columnExists('movies', 'actors');

        $extraCols = '';
        if ($hasDirector) $extraCols .= ', m.director';
        if ($hasActors)   $extraCols .= ', m.actors';

        $sql = "
            SELECT
                m.* {$extraCols},
                GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', ') AS genre_names,
                GROUP_CONCAT(DISTINCT g.genre_id ORDER BY g.genre_id SEPARATOR ',') AS genre_ids
            FROM movies m
            LEFT JOIN movie_genres mg ON mg.movie_id = m.movie_id
            LEFT JOIN genres g ON g.genre_id = mg.genre_id AND g.status = 1
            WHERE m.status IN (1, 2)
              AND (
                m.title LIKE ?
                OR m.description LIKE ?
        ";

        $like = '%' . $keyword . '%';
        $types = 'ss';
        $params = [$like, $like];

        if ($hasDirector) {
            $sql .= " OR m.director LIKE ?";
            $types .= 's';
            $params[] = $like;
        }

        if ($hasActors) {
            $sql .= " OR m.actors LIKE ?";
            $types .= 's';
            $params[] = $like;
        }

        $sql .= "
              )
            GROUP BY m.movie_id
            ORDER BY
                CASE WHEN m.status = 2 THEN 1 WHEN m.status = 1 THEN 2 ELSE 3 END,
                m.release_date DESC,
                m.movie_id DESC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        $result = $stmt->get_result();
        $movies = [];
        while ($row = $result->fetch_assoc()) {
            $movies[] = $row;
        }

        return $movies;
    }
}
