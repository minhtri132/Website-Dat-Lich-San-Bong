-- CHÚ Ý: file này tạo lại các bảng từ đầu (xóa dữ liệu cũ của 4 bảng users, courts, bookings, payments)
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS football_7_booking
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE football_7_booking;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS courts;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE courts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    surface VARCHAR(100) NOT NULL DEFAULT 'Cỏ nhân tạo',
    price_per_hour DECIMAL(12, 0) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE bookings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    court_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    customer_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,
    price_per_hour DECIMAL(12, 0) NOT NULL,
    total_price DECIMAL(12, 0) NOT NULL,
    -- Tiền cọc bắt buộc để giữ chỗ (0 = không cần cọc)
    deposit_amount DECIMAL(12, 0) NOT NULL DEFAULT 0,
    -- pending: đang giữ chỗ chờ đặt cọc | confirmed: đã xác nhận | cancelled: đã hủy
    status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
    -- Hết thời điểm này mà chưa đủ cọc thì tự hủy
    hold_until DATETIME NULL,
    -- Khách bấm "Tôi đã chuyển khoản" để báo cho admin kiểm tra
    transfer_reported TINYINT(1) NOT NULL DEFAULT 0,
    cancelled_at DATETIME NULL,
    cancel_reason VARCHAR(255) NULL,
    -- Số tiền cần hoàn lại cho khách khi hủy, và trạng thái hoàn
    refund_amount DECIMAL(12, 0) NOT NULL DEFAULT 0,
    refund_status ENUM('none', 'pending', 'done') NOT NULL DEFAULT 'none',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_court FOREIGN KEY (court_id) REFERENCES courts(id),
    CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_bookings_court_time (court_id, start_at, status),
    INDEX idx_bookings_start_status (start_at, status),
    INDEX idx_bookings_hold (status, hold_until)
) ENGINE=InnoDB;

-- Mỗi lần thu tiền là một dòng; hoàn tiền ghi số âm
CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(12, 0) NOT NULL,
    method ENUM('cash', 'transfer') NOT NULL,
    type ENUM('payment', 'refund') NOT NULL DEFAULT 'payment',
    note VARCHAR(255) NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id) REFERENCES bookings(id),
    CONSTRAINT fk_payments_user FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_payments_booking (booking_id),
    INDEX idx_payments_created (created_at)
) ENGINE=InnoDB;

-- Tài khoản admin mẫu: admin@example.com / admin123
-- Tài khoản khách mẫu: khach@example.com / 123456
INSERT INTO users (full_name, phone, email, password, role) VALUES
    ('Quản trị viên', '0900000000', 'admin@example.com', '$2y$10$NteUft9QiGlwbrv2EnguxOVux.lr7/i1RAmOEkdWh5P2mwwMh6/kG', 'admin'),
    ('Nguyễn Văn Khách', '0911111111', 'khach@example.com', '$2y$10$qLMVLcFIHhr8IhaNwJrLnuJRnmDjLB6DTspA69iY7cwUvewqzoxFy', 'customer');

INSERT INTO courts (name, surface, price_per_hour) VALUES
    ('Sân 1', 'Cỏ nhân tạo', 300000),
    ('Sân 2', 'Cỏ nhân tạo', 300000),
    ('Sân 3', 'Cỏ nhân tạo', 350000),
    ('Sân 4', 'Cỏ nhân tạo', 350000);