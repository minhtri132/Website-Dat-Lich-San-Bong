<?php
require __DIR__ . '/commons/env.php';
require __DIR__ . '/commons/function.php';

try {
    $pdo = connectDB();
    echo 'Kết nối OK<br>';
    foreach (['users', 'courts', 'bookings'] as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "Bảng $table: $count dòng<br>";
    }
} catch (Throwable $e) {
    echo 'LỖI: ' . htmlspecialchars($e->getMessage());
}