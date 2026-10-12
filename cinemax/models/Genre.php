<?php

class Genre
{
    private mysqli $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // Dùng cho checkbox genres trong movies
    public function getAllGenres(): array
    {
        $sql = "SELECT genre_id, name, status
                FROM genres
                WHERE status = 1
                ORDER BY name ASC";

        $rs = $this->conn->query($sql);
        $out = [];

        while ($row = $rs->fetch_assoc()) {
            $out[] = $row;
        }

        return $out;
    }

    // Dùng cho trang admin genres
    public function getGenresForAdmin(string $q = ''): array
    {
        $q = trim($q);

        $sql = "SELECT genre_id, name, status
                FROM genres
                WHERE status = 1";
        $types = "";
        $params = [];

        if ($q !== '') {
            $sql .= " AND name LIKE ?";
            $types = "s";
            $params[] = "%{$q}%";
        }

        $sql .= " ORDER BY genre_id DESC";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        if ($types !== "") {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();

        $rs = $stmt->get_result();
        $out = [];

        while ($row = $rs->fetch_assoc()) {
            $out[] = $row;
        }

        return $out;
    }

    public function existsActiveName(string $name, int $excludeId = 0): bool
    {
        $sql = "SELECT 1
                FROM genres
                WHERE status = 1 AND name = ? AND genre_id <> ?
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param("si", $name, $excludeId);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();
        return (bool)$row;
    }

    public function countActiveByIds(array $ids): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids), fn($x) => $x > 0));
        if (count($ids) === 0) return 0;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));

        $sql = "SELECT COUNT(*) AS cnt
                FROM genres
                WHERE status = 1 AND genre_id IN ($placeholders)";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param($types, ...$ids);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();
        return (int)($row['cnt'] ?? 0);
    }

    public function createGenre(string $name): bool
    {
        $sql = "INSERT INTO genres (name, status) VALUES (?, 1)";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param("s", $name);
        return $stmt->execute();
    }

    public function existsById(int $id): bool
    {
        $sql = "SELECT 1 FROM genres WHERE genre_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();
        return (bool)$row;
    }

    public function isGenreUsedInMovies(int $id): bool
    {
        $sql = "SELECT 1
                FROM movie_genres
                WHERE genre_id = ?
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();
        return (bool)$row;
    }

    public function updateGenre(int $id, string $name): bool
    {
        $stmt = $this->conn->prepare("UPDATE genres SET name = ? WHERE genre_id = ?");
        $stmt->bind_param("si", $name, $id);
        return $stmt->execute();
    }

    public function deleteGenre(int $id): bool
    {
        $sql = "DELETE FROM genres WHERE genre_id = ?";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
