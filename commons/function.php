<?php

function connectDB(): PDO
{
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        DB_HOST,
        DB_PORT,
        DB_NAME
    );

    return new PDO($dsn, DB_USERNAME, DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

// Escape dữ liệu trước khi in ra HTML (chống XSS)
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function assertCsrf(): void
{
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        throw new DomainException('Phiên làm việc không hợp lệ. Vui lòng tải lại trang.');
    }
}

function render(string $view, array $data = []): void
{
    extract($data);
    require PATH_ROOT . 'views/' . $view . '.php';
}

function redirect(string $act, array $params = []): never
{
    $query = http_build_query(['act' => $act] + $params);
    header('Location: index.php?' . $query);
    exit;
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function pullFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function isAdmin(): bool
{
    return (currentUser()['role'] ?? '') === 'admin';
}

function requireLogin(): void
{
    if (!currentUser()) {
        setFlash('error', 'Vui lòng đăng nhập để tiếp tục.');
        redirect('login');
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        setFlash('error', 'Bạn không có quyền truy cập trang này.');
        redirect('/');
    }
}

function formatMoney($value): string
{
    return number_format((float) $value, 0, ',', '.') . ' đ';
}

// Trả về 'Y-m-d' nếu hợp lệ, ngược lại trả về null
function parseDate($value): ?string
{
    if (!is_string($value)) {
        return null;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : null;
}

// Tiền cọc bắt buộc, làm tròn lên đến nghìn đồng và không vượt quá tổng tiền
function depositFor(float $total): int
{
    if (DEPOSIT_PERCENT <= 0) {
        return 0;
    }
    $deposit = (int) (ceil($total * DEPOSIT_PERCENT / 100 / 1000) * 1000);

    return (int) min($deposit, $total);
}

// Trả về [nhãn, lớp CSS] mô tả tình trạng của một lịch đặt
function bookingState(array $booking): array
{
    $paid = (float) ($booking['paid_amount'] ?? 0);
    $total = (float) $booking['total_price'];

    if ($booking['status'] === 'cancelled') {
        return ['Đã hủy', 'cancelled'];
    }
    if ($booking['status'] === 'pending') {
        return ['Chờ đặt cọc', 'pending'];
    }
    if (new DateTimeImmutable($booking['end_at']) <= new DateTimeImmutable()) {
        return ['Hoàn thành', 'done'];
    }
    if ($paid >= $total) {
        return ['Đã thanh toán', 'paid'];
    }
    if ($paid > 0) {
        return ['Đã đặt cọc', 'confirmed'];
    }

    return ['Đã xác nhận', 'confirmed'];
}

function bookingStatusText(array $booking): string
{
    return bookingState($booking)[0];
}

function bookingStatusClass(array $booking): string
{
    return bookingState($booking)[1];
}

// Số tiền khách còn phải trả
function remainingAmount(array $booking): float
{
    return max(0.0, (float) $booking['total_price'] - (float) ($booking['paid_amount'] ?? 0));
}

function paymentMethodText(string $method): string
{
    return $method === 'cash' ? 'Tiền mặt' : 'Chuyển khoản';
}

// Chỉ hủy được lịch đang giữ chỗ hoặc đã xác nhận và chưa bắt đầu
function canCancelBooking(array $booking): bool
{
    return in_array($booking['status'], ['pending', 'confirmed'], true)
        && new DateTimeImmutable($booking['start_at']) > new DateTimeImmutable();
}