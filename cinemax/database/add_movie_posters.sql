-- Poster also serves as the movie avatar in the current UI.
-- Image files are stored under public/assets/uploads/movies.
SET NAMES utf8mb4;
START TRANSACTION;

UPDATE movies SET poster_url = 'assets/uploads/movies/avengers-endgame.jpg' WHERE title = 'Avengers: Endgame';
UPDATE movies SET poster_url = 'assets/uploads/movies/your-name.jpg' WHERE title = 'Your Name';
UPDATE movies SET poster_url = 'assets/uploads/movies/inception.jpg' WHERE title = 'Inception';
UPDATE movies SET poster_url = 'assets/uploads/movies/avatar-fire-and-ash.jpg' WHERE title = 'AVATA 3 (LỬA VÀ TRO TÀN)';
UPDATE movies SET poster_url = 'assets/uploads/movies/avatar-way-of-water.jpg' WHERE title = 'Avatar: The Way of Water';
UPDATE movies SET poster_url = 'assets/uploads/movies/dem-ngay-xa-me.jpg' WHERE title IN ('Đêm Ngày Xa Mẹ', 'Đếm Ngày Xa Mẹ');
UPDATE movies SET poster_url = 'assets/uploads/movies/quy-nhap-trang-2.jpg' WHERE title = 'Quỷ Nhập Tràng 2';

INSERT INTO movie_images (movie_id, image_url, is_poster, status)
SELECT m.movie_id, m.poster_url, 1, 1
FROM movies m
WHERE m.poster_url IN (
    'assets/uploads/movies/avengers-endgame.jpg',
    'assets/uploads/movies/your-name.jpg',
    'assets/uploads/movies/inception.jpg',
    'assets/uploads/movies/avatar-fire-and-ash.jpg',
    'assets/uploads/movies/avatar-way-of-water.jpg',
    'assets/uploads/movies/dem-ngay-xa-me.jpg',
    'assets/uploads/movies/quy-nhap-trang-2.jpg'
)
AND NOT EXISTS (
    SELECT 1 FROM movie_images i
    WHERE i.movie_id = m.movie_id AND i.image_url = m.poster_url
);

COMMIT;
