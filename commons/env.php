<?php

date_default_timezone_set('Asia/Ho_Chi_Minh');

// Cấu hình kết nối MySQL (Laragon mặc định: root, mật khẩu rỗng)
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '123456');
define('DB_NAME', 'football_7_booking');

define('PATH_ROOT', __DIR__ . '/../');

// Giờ mở cửa: nhận đặt từ 06:00, trận cuối kết thúc lúc 23:00
define('OPEN_HOUR', 6);
define('CLOSE_HOUR', 23);
// Số giờ tối đa cho một lần đặt
define('MAX_HOURS', 3);
// Thông tin liên hệ hiển thị ở chân trang (sửa lại cho đúng với sân của bạn)
define('SITE_PHONE', '0123456 789');
define('SITE_ADDRESS', 'Số 36, Đường Đường Chính Chính, Hà Nội');