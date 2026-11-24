
<?php

ini_set('display_errors', '0');
ini_set('log_errors', '1');

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/commons/env.php';
require_once __DIR__ . '/commons/function.php';
require_once __DIR__ . '/models/BookingModel.php';


$secret = 'a2094981a3c12a3091192db3d94ee6bd78e521a8f7cf93bc8800026eda9e1e43';

function webhookResponse(int $status, array $data): void
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    webhookResponse(405, [
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}

$body = file_get_contents('php://input');

if ($body === false || $body === '') {
    webhookResponse(400, [
        'success' => false,
        'message' => 'Empty request body'
    ]);
}


$timestamp = $_SERVER['HTTP_X_SEPAY_TIMESTAMP'] ?? '';
$signature = $_SERVER['HTTP_X_SEPAY_SIGNATURE'] ?? '';

if ($timestamp === '' || $signature === '') {
    webhookResponse(401, [
        'success' => false,
        'message' => 'Missing signature'
    ]);
}

if (!preg_match('/^\d+$/', $timestamp)) {
    webhookResponse(401, [
        'success' => false,
        'message' => 'Invalid timestamp'
    ]);
}

$timestampValue = (int) $timestamp;

if (abs(time() - $timestampValue) > 300) {
    webhookResponse(401, [
        'success' => false,
        'message' => 'Request expired'
    ]);
}

$expectedSignature = 'sha256=' . hash_hmac(
    'sha256',
    $timestamp . '.' . $body,
    $secret
);

if (!hash_equals($expectedSignature, $signature)) {
    error_log('SePay webhook: Invalid signature');

    webhookResponse(401, [
        'success' => false,
        'message' => 'Invalid signature'
    ]);
}


$data = json_decode($body, true);

if (
    !is_array($data) ||
    !isset($data['id']) ||
    !isset($data['transferType'])
) {
    webhookResponse(400, [
        'success' => false,
        'message' => 'Invalid transaction payload'
    ]);
}

/*
|--------------------------------------------------------------------------
| VALIDATE TRANSACTION
|--------------------------------------------------------------------------
*/

if ($data['transferType'] !== 'in') {
    webhookResponse(200, [
        'success' => true,
        'status' => 'ignored',
        'message' => 'Not an incoming transaction'
    ]);
}

/*
|--------------------------------------------------------------------------
| PROCESS TRANSACTION
|--------------------------------------------------------------------------
*/

try {

    $bookingModel = new BookingModel();

    $result = $bookingModel->processSePayTransaction($data);

    file_put_contents(
    __DIR__ . '/sepay_debug.log',
    date('Y-m-d H:i:s') . ' - ' .
    json_encode($result, JSON_UNESCAPED_UNICODE) .
    PHP_EOL,
    FILE_APPEND
);

    if (!is_array($result)) {
        throw new RuntimeException(
            'Invalid result from processSePayTransaction'
        );
    }

    $resultStatus = $result['status'] ?? 'unknown';

    error_log(
        'SePay transaction: ' .
        $resultStatus .
        ', transaction_id=' .
        (string) $data['id']
    );

    /*
     * Các trạng thái nghiệp vụ cần kiểm tra.
     * Không đồng nghĩa với lỗi kết nối hoặc lỗi PHP.
     */

    $allowedStatuses = [
        'processed',
        'duplicate',
        'ignored'
    ];

    if (!in_array($resultStatus, $allowedStatuses, true)) {

        error_log(
            'SePay transaction requires manual review: ' .
            json_encode($result, JSON_UNESCAPED_UNICODE)
        );
    }

    webhookResponse(200, [
        'success' => true,
        'status' => $resultStatus
    ]);

} catch (Throwable $e) {
    file_put_contents(
    __DIR__ . '/sepay_debug.log',
    date('Y-m-d H:i:s') . ' - ERROR: ' .
    $e->getMessage() .
    PHP_EOL,
    FILE_APPEND
);
    error_log(
        'SePay webhook error: ' .
        $e->getMessage()
    );

    error_log(
        'SePay failed transaction ID: ' .
        (string) ($data['id'] ?? 'unknown')
    );

    webhookResponse(500, [
        'success' => false,
        'message' => 'Internal server error'
    ]);
}
