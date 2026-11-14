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

// ===== Cấu hình đặt cọc và thanh toán =====
// Phần trăm tiền cọc trên tổng tiền (đặt 0 nếu không yêu cầu cọc)
define('DEPOSIT_PERCENT', 30);
// Số phút giữ chỗ để khách đặt cọc, quá hạn lịch tự hủy
define('HOLD_MINUTES', 15);
// Hủy trước giờ đá ít nhất bao nhiêu giờ thì được hoàn cọc (hủy sát giờ sẽ mất cọc)
define('CANCEL_FREE_HOURS', 24);
// Số lịch chờ đặt cọc tối đa của một khách cùng lúc (chống giữ chỗ ảo)
define('MAX_PENDING_BOOKINGS', 3);

// Tài khoản nhận tiền chuyển khoản (sửa lại cho đúng)
define('BANK_NAME', 'Vietcombank');
define('BANK_ACCOUNT', '0123456789');
define('BANK_ACCOUNT_NAME', 'NGUYEN VAN A');
// Mã ngân hàng theo VietQR (ví dụ VCB, MB, ACB, TCB). Để rỗng nếu không muốn hiện mã QR
define('BANK_ID', '');