USE cinemax;

-- DỮ LIỆU MẪU CHO DASHBOARD / THỐNG KÊ

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE bill_combos;
TRUNCATE TABLE tickets;
TRUNCATE TABLE bills;
TRUNCATE TABLE shows;
TRUNCATE TABLE seats;
TRUNCATE TABLE seat_types;
TRUNCATE TABLE halls;
TRUNCATE TABLE cinemas;
TRUNCATE TABLE locations;
TRUNCATE TABLE movie_genres;
TRUNCATE TABLE movies;
TRUNCATE TABLE genres;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

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

-- MỘT ÍT GHẾ MẪU
INSERT INTO seats (seat_id, hall_id, seat_type_id, row_name, seat_number, status) VALUES
(1, 1, 1, 'A', 1, 1),
(2, 1, 1, 'A', 2, 1),
(3, 1, 2, 'A', 3, 1),
(4, 2, 1, 'B', 1, 1),
(5, 3, 1, 'A', 1, 1);

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
(1, 1, 'Admin Demo', 'admin@demo.com', 'dummy', '0900000001', 1, '2024-01-01 09:00:00'),
(2, 2, 'Nguyễn Văn A', 'user1@demo.com', 'dummy', '0900000002', 1, CURDATE()),
(3, 2, 'Trần Thị B', 'user2@demo.com', 'dummy', '0900000003', 1, DATE_SUB(CURDATE(), INTERVAL 5 DAY));

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

-- TICKETS
INSERT INTO tickets (ticket_id, bill_id, show_id, seat_name, price, status)
VALUES
(1, 1, 1, 'A1', 100000, 'paid'),
(2, 1, 1, 'A2', 100000, 'paid'),
(3, 1, 1, 'A3', 100000, 'paid'),
(4, 2, 3, 'A1', 100000, 'paid'),
(5, 2, 3, 'A2', 100000, 'paid'),
(6, 3, 4, 'A1', 110000, 'paid'),
(7, 3, 4, 'A2', 110000, 'paid'),
(8, 4, 5, 'A1', 110000, 'paid'),
(9, 4, 5, 'A2', 110000, 'paid'),
(10,4, 5, 'A3', 110000, 'paid'),
(11,4, 5, 'A4', 110000, 'paid'),
(12,5, 6, 'A1', 120000, 'paid');

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
