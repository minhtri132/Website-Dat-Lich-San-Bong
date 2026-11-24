USE football_7_booking;

CREATE TABLE IF NOT EXISTS extra_products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    category VARCHAR(30) NOT NULL DEFAULT 'water',
    name VARCHAR(100) NOT NULL,
    price DECIMAL(12, 0) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS booking_extras (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id BIGINT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    product_name VARCHAR(100) NOT NULL,
    unit_price DECIMAL(12, 0) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    line_total DECIMAL(12, 0) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_booking_extras_booking_v2 FOREIGN KEY (booking_id) REFERENCES bookings(id),
    CONSTRAINT fk_booking_extras_product_v2 FOREIGN KEY (product_id) REFERENCES extra_products(id),
    INDEX idx_booking_extras_booking_v2 (booking_id)
) ENGINE=InnoDB;

UPDATE extra_products SET category = 'gloves'
WHERE code = 'goalkeeper_gloves' OR name LIKE '%Găng%' OR name LIKE '%găng%';
UPDATE extra_products SET category = 'shoes'
WHERE code = 'shoes' OR name LIKE '%Giày%' OR name LIKE '%giày%';
UPDATE extra_products SET category = 'water'
WHERE code = 'water' OR name LIKE '%Nước%' OR name LIKE '%nước%';

INSERT INTO extra_products (code, category, name, price) VALUES
    ('water_default', 'water', 'Nước khoáng', 15000),
    ('goalkeeper_gloves_default', 'gloves', 'Găng bắt gôn', 50000),
    ('shoes_default', 'shoes', 'Giày đá banh', 80000)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    price = VALUES(price),
    is_active = 1;
