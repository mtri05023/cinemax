    CREATE DATABASE IF NOT EXISTS cinemax
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

    USE cinemax;

    -- ============================================================================
    -- 1. HỆ THỐNG NGƯỜI DÙNG & QUYỀN
    -- ============================================================================

    CREATE TABLE IF NOT EXISTS roles (
        role_id       INT AUTO_INCREMENT PRIMARY KEY,
        role_name     VARCHAR(50) NOT NULL UNIQUE,
        description   VARCHAR(255),
        status        TINYINT(1) NOT NULL DEFAULT 1
    );

    CREATE TABLE IF NOT EXISTS permissions (
        permission_id   INT AUTO_INCREMENT PRIMARY KEY,
        permission_code VARCHAR(100) NOT NULL UNIQUE,
        description     VARCHAR(255),
        status          TINYINT(1) NOT NULL DEFAULT 1
    );

    CREATE TABLE IF NOT EXISTS role_permissions (
        role_id       INT NOT NULL,
        permission_id INT NOT NULL,
        PRIMARY KEY (role_id, permission_id),
        FOREIGN KEY (role_id)       REFERENCES roles(role_id)       ON DELETE CASCADE,
        FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS users (
        user_id       INT AUTO_INCREMENT PRIMARY KEY,
        role_id       INT NOT NULL,
        full_name     VARCHAR(100) NOT NULL,
        email         VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        phone         VARCHAR(20),
        status        TINYINT(1) NOT NULL DEFAULT 1,
        created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login    TIMESTAMP NULL DEFAULT NULL,
        FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE RESTRICT
    );

    INSERT IGNORE INTO roles (role_id, role_name, description) VALUES
        (1, 'Admin', 'Quản trị viên'),
        (2, 'User', 'Người dùng thường');

    -- ============================================================================
    -- 2. ĐỊA ĐIỂM & RẠP CHIẾU
    -- ============================================================================

    CREATE TABLE IF NOT EXISTS locations (
        location_id   INT AUTO_INCREMENT PRIMARY KEY,
        name          VARCHAR(100) NOT NULL UNIQUE
    );

    CREATE TABLE IF NOT EXISTS cinemas (
        cinema_id     INT AUTO_INCREMENT PRIMARY KEY,
        name          VARCHAR(150) NOT NULL,
        address       VARCHAR(255) NOT NULL,
        location_id   INT NOT NULL,
        status        TINYINT(1) NOT NULL DEFAULT 1,
        created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (location_id) REFERENCES locations(location_id) ON DELETE RESTRICT
    );

    -- ============================================================================
    -- 3. PHIM & THỂ LOẠI
    -- ============================================================================

    CREATE TABLE IF NOT EXISTS movies (
        movie_id      INT AUTO_INCREMENT PRIMARY KEY,
        title         VARCHAR(255) NOT NULL,
        director      VARCHAR(150) NOT NULL DEFAULT '',
        actors        TEXT,
        description   TEXT,
        duration_min  INT NOT NULL CHECK (duration_min > 0),
        release_date  DATE,
        poster_url    VARCHAR(255),
        trailer_url   VARCHAR(255),
        status        TINYINT(1) NOT NULL DEFAULT 1,
        created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS genres (
        genre_id   INT AUTO_INCREMENT PRIMARY KEY,
        name       VARCHAR(100) NOT NULL UNIQUE,
        status     TINYINT(1) NOT NULL DEFAULT 1
    );

    CREATE TABLE IF NOT EXISTS movie_genres (
        movie_id   INT NOT NULL,
        genre_id   INT NOT NULL,
        PRIMARY KEY (movie_id, genre_id),
        FOREIGN KEY (movie_id) REFERENCES movies(movie_id) ON DELETE CASCADE,
        FOREIGN KEY (genre_id) REFERENCES genres(genre_id) ON DELETE CASCADE
    );

    -- ============================================================================
    -- 4. PHÒNG CHIẾU & GHẾ NGỒI
    -- ============================================================================

    CREATE TABLE IF NOT EXISTS halls (
        hall_id       INT AUTO_INCREMENT PRIMARY KEY,
        cinema_id     INT NOT NULL,
        name          VARCHAR(50) NOT NULL,
        total_seats   INT NOT NULL DEFAULT 0,
        status        TINYINT(1) NOT NULL DEFAULT 1,
        FOREIGN KEY (cinema_id) REFERENCES cinemas(cinema_id) ON DELETE CASCADE,
        UNIQUE KEY uk_cinema_hall (cinema_id, name)
    );

    CREATE TABLE IF NOT EXISTS seat_types (
        seat_type_id     INT AUTO_INCREMENT PRIMARY KEY,
        type_name        VARCHAR(50) NOT NULL UNIQUE,
        price_multiplier DECIMAL(4,2) NOT NULL DEFAULT 1.00,
        status           TINYINT(1) NOT NULL DEFAULT 1
    );

    CREATE TABLE IF NOT EXISTS seats (
        seat_id       INT AUTO_INCREMENT PRIMARY KEY,
        hall_id       INT NOT NULL,
        seat_type_id  INT NOT NULL,
        row_name      CHAR(2) NOT NULL,
        seat_number   INT NOT NULL,
        status        TINYINT(1) NOT NULL DEFAULT 1,
        FOREIGN KEY (hall_id)      REFERENCES halls(hall_id)      ON DELETE CASCADE,
        FOREIGN KEY (seat_type_id) REFERENCES seat_types(seat_type_id) ON DELETE RESTRICT,
        UNIQUE KEY uk_seat_position (hall_id, row_name, seat_number)
    );

    -- ============================================================================
    -- 5. SUẤT CHIẾU
    -- ============================================================================

    CREATE TABLE IF NOT EXISTS shows (
        show_id       INT AUTO_INCREMENT PRIMARY KEY,
        movie_id      INT NOT NULL,
        hall_id       INT NOT NULL,
        show_date     DATE NOT NULL,
        start_time    TIME NOT NULL,
        end_time      TIME NOT NULL,
        base_price    DECIMAL(12,2) NOT NULL DEFAULT 0,
        status        TINYINT(1) NOT NULL DEFAULT 1,
        created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (movie_id) REFERENCES movies(movie_id)  ON DELETE RESTRICT,
        FOREIGN KEY (hall_id)  REFERENCES halls(hall_id)    ON DELETE RESTRICT,
        UNIQUE KEY uk_show_unique (hall_id, show_date, start_time)
    );

    -- ============================================================================
    -- 6. ĐẶT VÉ, HÓA ĐƠN, KHUYẾN MÃI, COMBO
    -- ============================================================================

    CREATE TABLE IF NOT EXISTS promotions (
        promotion_id     INT AUTO_INCREMENT PRIMARY KEY,
        code             VARCHAR(50) NOT NULL UNIQUE,
        name             VARCHAR(150) NOT NULL,
        discount_type    ENUM('percent', 'fixed') NOT NULL DEFAULT 'percent',
        discount_value   DECIMAL(12,2) NOT NULL DEFAULT 1.00,
        discount_percent DECIMAL(5,2) NOT NULL CHECK (discount_percent BETWEEN 0 AND 100),
        start_date       DATETIME NOT NULL,
          end_date         DATETIME NOT NULL,
          min_amount       DECIMAL(12,2) DEFAULT 0,
          usage_limit      INT NOT NULL DEFAULT 0,
          status           TINYINT(1) NOT NULL DEFAULT 1,
        created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS bills (
        bill_id         INT AUTO_INCREMENT PRIMARY KEY,
        user_id         INT NOT NULL,
        total_tickets   INT NOT NULL,
        total_amount    DECIMAL(12,2) NOT NULL,
        discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        final_amount    DECIMAL(12,2) NOT NULL,
        promotion_id    INT NULL,
        status          ENUM('pending', 'paid', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending',
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        paid_at         TIMESTAMP NULL DEFAULT NULL,
        FOREIGN KEY (user_id)      REFERENCES users(user_id)      ON DELETE RESTRICT,
        FOREIGN KEY (promotion_id) REFERENCES promotions(promotion_id) ON DELETE SET NULL
    );

    CREATE TABLE IF NOT EXISTS tickets (
        ticket_id  INT AUTO_INCREMENT PRIMARY KEY,

        show_id    INT NOT NULL,
        seat_id    INT NOT NULL,

        bill_id    INT NULL,

        price      DECIMAL(12,2) NOT NULL,

        status ENUM('available','booked','paid','used','cancelled') 
            NOT NULL DEFAULT 'available',

        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

        FOREIGN KEY (show_id) REFERENCES shows(show_id) ON DELETE RESTRICT,
        FOREIGN KEY (seat_id) REFERENCES seats(seat_id) ON DELETE RESTRICT,
        FOREIGN KEY (bill_id) REFERENCES bills(bill_id) ON DELETE CASCADE,

        UNIQUE KEY uk_ticket_seat_show (show_id, seat_id)
    );

    CREATE TABLE IF NOT EXISTS combos (
        combo_id    INT AUTO_INCREMENT PRIMARY KEY,
        name        VARCHAR(100) NOT NULL,
        description VARCHAR(255),
        image_url   VARCHAR(255),
        price       DECIMAL(12,2) NOT NULL,
        status      TINYINT(1) NOT NULL DEFAULT 1
    );

    CREATE TABLE IF NOT EXISTS bill_combos (
        bill_id   INT NOT NULL,
        combo_id  INT NOT NULL,
        quantity  INT NOT NULL CHECK (quantity > 0),
        price     DECIMAL(12,2) NOT NULL,
        PRIMARY KEY (bill_id, combo_id),
        FOREIGN KEY (bill_id)  REFERENCES bills(bill_id)   ON DELETE CASCADE,
        FOREIGN KEY (combo_id) REFERENCES combos(combo_id) ON DELETE RESTRICT
    );

    -- ============================================================================
    -- 7. HÌNH ẢNH PHIM
    -- ============================================================================

    CREATE TABLE IF NOT EXISTS movie_images (
        image_id   INT AUTO_INCREMENT PRIMARY KEY,
        movie_id   INT NOT NULL,
        image_url  VARCHAR(255) NOT NULL,
        is_poster  TINYINT(1) NOT NULL DEFAULT 0,
        status     TINYINT(1) NOT NULL DEFAULT 1,
        FOREIGN KEY (movie_id) REFERENCES movies(movie_id) ON DELETE CASCADE
    );
