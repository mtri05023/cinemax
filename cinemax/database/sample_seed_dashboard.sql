USE cinemax;

-- DỮ LIỆU MẪU CHO DASHBOARD / THỐNG KÊ
-- CHỈ DÙNG CHO DEMO: file này xóa dữ liệu cũ trong các bảng bên dưới.
-- Tài khoản: admin@demo.com, user1@demo.com, user2@demo.com
-- Mật khẩu demo cho cả 3 tài khoản: Cinemax@123
SET NAMES utf8mb4;

-- Xóa bảng con trước bảng cha; giữ kiểm tra khóa ngoại khi import.
START TRANSACTION;
DELETE FROM bill_combos;
DELETE FROM tickets;
DELETE FROM bills;
DELETE FROM shows;
DELETE FROM seats;
DELETE FROM seat_types;
DELETE FROM halls;
DELETE FROM cinemas;
DELETE FROM locations;
DELETE FROM movie_images;
DELETE FROM movie_genres;
DELETE FROM movies;
DELETE FROM genres;
DELETE FROM users;

-- LOCATIONS
INSERT INTO locations (location_id, name) VALUES
(1, 'Quận 1'),
(2, 'Quận 7'),
(3, 'Thủ Đức');

-- CINEMAS
INSERT INTO cinemas (cinema_id, name, address, location_id, status, created_at) VALUES
(1, 'Cinemax Diamond', '123 Lê Lợi, Quận 1', 1, 1, '2024-01-10 10:00:00'),
(2, 'Cinemax Riverside', '45 Nguyễn Văn Linh, Quận 7', 2, 1, '2024-02-15 11:00:00'),
(3, 'Cinemax Thủ Đức', '89 Võ Văn Ngân, Thủ Đức', 3, 0, '2024-03-20 09:00:00');

-- HALLS
INSERT INTO halls (hall_id, cinema_id, name, total_seats, status) VALUES
(1, 1, 'Phòng 1', 50, 1),
(2, 1, 'Phòng 2', 60, 1),
(3, 2, 'Phòng 1', 80, 1),
(4, 3, 'Phòng 1', 40, 0);

-- SEAT TYPES
INSERT INTO seat_types (seat_type_id, type_name, price_multiplier, status) VALUES
(1, 'Standard', 1.00, 1),
(2, 'VIP', 1.50, 1);

-- Tạo đủ ghế theo total_seats của từng phòng, 10 ghế mỗi hàng.
INSERT INTO seats (hall_id, seat_type_id, row_name, seat_number, status)
SELECT h.hall_id,
       CASE WHEN MOD(n.num, 10) >= 8 THEN 2 ELSE 1 END,
       CHAR(65 + FLOOR(n.num / 10)),
       MOD(n.num, 10) + 1,
       1
FROM halls h
CROSS JOIN (
    SELECT tens.digit * 10 + units.digit AS num
    FROM (SELECT 0 digit UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3
          UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7
          UNION ALL SELECT 8 UNION ALL SELECT 9) tens
    CROSS JOIN (SELECT 0 digit UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3
          UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7
          UNION ALL SELECT 8 UNION ALL SELECT 9) units
) n
WHERE n.num < h.total_seats
ORDER BY h.hall_id, n.num;
-- GENRES
INSERT INTO genres (genre_id, name, status) VALUES
(1, 'Hành động', 1),
(2, 'Tình cảm', 1);

-- MOVIES
INSERT INTO movies (movie_id, title, description, duration_min, release_date, poster_url, trailer_url, status, created_at)
VALUES
(1, 'Avengers: Endgame', 'Siêu anh hùng Marvel', 180, '2019-04-26', NULL, NULL, 1, '2024-01-01 00:00:00'),
(2, 'Your Name', 'Anime tình cảm', 110, '2016-08-26', NULL, NULL, 1, '2024-02-01 00:00:00'),
(3, 'Inception', 'Hành động, khoa học viễn tưởng', 148, '2010-07-16', NULL, NULL, 0, '2024-03-01 00:00:00');

INSERT INTO movie_genres (movie_id, genre_id) VALUES
(1, 1),
(2, 2),
(3, 1);

-- USERS (1 admin + 2 user)
INSERT INTO users (user_id, role_id, full_name, email, password_hash, phone, status, created_at)
VALUES
(1, 1, 'Admin Demo', 'admin@demo.com', '$2y$10$JVAANl/btFwG1kUa2QqFteDTgmvZTfWGMMYgmGprBRss9Pt0KFwau', '0900000001', 1, '2024-01-01 09:00:00'),
(2, 2, 'Nguyễn Văn A', 'user1@demo.com', '$2y$10$JVAANl/btFwG1kUa2QqFteDTgmvZTfWGMMYgmGprBRss9Pt0KFwau', '0900000002', 1, CURDATE()),
(3, 2, 'Trần Thị B', 'user2@demo.com', '$2y$10$JVAANl/btFwG1kUa2QqFteDTgmvZTfWGMMYgmGprBRss9Pt0KFwau', '0900000003', 1, DATE_SUB(CURDATE(), INTERVAL 5 DAY));

-- SHOWS
INSERT INTO shows (show_id, movie_id, hall_id, show_date, start_time, end_time, base_price, status, created_at)
VALUES
(1, 1, 1, CURDATE(), '10:00:00', '13:00:00', 100000, 1, NOW()),
(2, 1, 2, CURDATE(), '20:00:00', '23:00:00', 120000, 1, NOW()),
(3, 2, 3, CURDATE(), '18:00:00', '20:00:00', 90000, 1, NOW()),
(4, 1, 1, '2024-01-15', '19:00:00', '22:00:00', 100000, 1, '2024-01-15 10:00:00'),
(5, 2, 1, '2024-02-10', '19:00:00', '21:00:00', 90000, 1, '2024-02-10 10:00:00'),
(6, 1, 3, '2024-03-20', '19:00:00', '22:00:00', 110000, 1, '2024-03-20 10:00:00');

-- BILLS
INSERT INTO bills (bill_id, user_id, total_tickets, total_amount, discount_amount, final_amount, promotion_id, status, created_at, paid_at)
VALUES
(1, 2, 3, 300000, 0, 300000, NULL, 'paid', CONCAT(CURDATE(), ' 09:00:00'), CONCAT(CURDATE(), ' 09:05:00')),
(2, 3, 2, 200000, 0, 200000, NULL, 'paid', CONCAT(CURDATE(), ' 14:00:00'), CONCAT(CURDATE(), ' 14:05:00')),
(3, 2, 2, 220000, 0, 220000, NULL, 'paid', '2024-01-20 11:00:00', '2024-01-20 11:05:00'),
(4, 3, 4, 440000, 0, 440000, NULL, 'paid', '2024-02-15 15:00:00', '2024-02-15 15:05:00'),
(5, 2, 1, 120000, 0, 120000, NULL, 'paid', '2024-03-25 20:00:00', '2024-03-25 20:02:00');

-- TICKETS: liên kết seat_id với đúng phòng của suất chiếu.
INSERT INTO tickets (ticket_id, bill_id, show_id, seat_id, price, status)
SELECT d.ticket_id, d.bill_id, d.show_id, s.seat_id, d.price, 'paid'
FROM (
    SELECT 1 ticket_id, 1 bill_id, 1 show_id, 1 seat_number, 100000 price
    UNION ALL SELECT 2,1,1,2,100000
    UNION ALL SELECT 3,1,1,3,100000
    UNION ALL SELECT 4,2,3,1,100000
    UNION ALL SELECT 5,2,3,2,100000
    UNION ALL SELECT 6,3,4,1,110000
    UNION ALL SELECT 7,3,4,2,110000
    UNION ALL SELECT 8,4,5,1,110000
    UNION ALL SELECT 9,4,5,2,110000
    UNION ALL SELECT 10,4,5,3,110000
    UNION ALL SELECT 11,4,5,4,110000
    UNION ALL SELECT 12,5,6,1,120000
) d
JOIN shows sh ON sh.show_id = d.show_id
JOIN seats s ON s.hall_id = sh.hall_id AND s.row_name = 'A' AND s.seat_number = d.seat_number;

-- Vé còn trống để có thể thử đặt vé ở các suất mẫu.
INSERT INTO tickets (show_id, seat_id, price, status)
SELECT sh.show_id, s.seat_id, sh.base_price * st.price_multiplier, 'available'
FROM shows sh
JOIN seats s ON s.hall_id = sh.hall_id AND s.status = 1
JOIN seat_types st ON st.seat_type_id = s.seat_type_id
LEFT JOIN tickets t ON t.show_id = sh.show_id AND t.seat_id = s.seat_id
WHERE t.ticket_id IS NULL;
INSERT INTO movies (
    movie_id,
    title,
    director,
    actors,
    description,
    duration_min,
    release_date,
    poster_url,
    trailer_url,
    status,
    created_at
) VALUES
(
    29,
    'AVATA 3 (LỬA VÀ TRO TÀN)',
    'James Cameron',
    'Sam Worthington, Zoe Saldaña',
    'Sau cuộc chiến tàn khốc với RDA và nỗi mất mát lớn, gia đình Sully tiếp tục đối mặt với những hiểm họa mới trên Pandora.',
    195,
    '2026-03-01',
    'assets/uploads/movies/movie_1774237707_9638.jpg',
    'https://youtu.be/GatNL0mmQGc?si=QbhgLzJ2PfR8YEbp',
    3,
    '2026-03-23 10:48:27'
),
(
    30,
    'Avatar: The Way of Water',
    'James Cameron',
    'Sam Worthington, Zoe Saldaña, Sigourney Weaver, Stephen Lang',
    'Lấy bối cảnh hơn 10 năm sau phần đầu, phim kể về gia đình Jake Sully và những thử thách họ phải đối mặt để bảo vệ nhau.',
    192,
    '2026-03-15',
    'assets/uploads/movies/movie_1774238202_7814.webp',
    'https://youtu.be/d9MyW72ELq0',
    2,
    '2026-03-23 10:56:42'
),
(
    31,
    'Đêm Ngày Xa Mẹ',
    'Trấn Thành',
    'Hari Won',
    'Bộ phim xoay quanh câu chuyện gia đình và tình cảm mẹ con với nhiều cảm xúc sâu lắng.',
    100,
    '2026-03-22',
    'assets/uploads/movies/movie_1774240308_6904.jpg',
    'https://www.youtube.com/results?search_query=Quỷ+N...',
    2,
    '2026-03-23 11:31:48'
),
(
    32,
    'Quỷ Nhập Tràng 2',
    'Trấn Thành',
    'Hari Won',
    'Tiếp nối phần trước, bộ phim khai thác những hiện tượng kỳ bí và yếu tố kinh dị tâm linh.',
    95,
    '2026-03-18',
    'assets/uploads/movies/movie_1774241056_9756.jpg',
    'https://www.youtube.com/results?search_query=Quỷ+N...',
    2,
    '2026-03-23 11:44:16'
);

COMMIT;
