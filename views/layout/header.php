<?php
/** @var ?string $title */
/** @var ?string $pageError */
/** @var ?bool $fullWidth */
$authUser = currentUser();
$flash = pullFlash();
$fullWidth = !empty($fullWidth);
$currentAct = $_GET['act'] ?? '/';
$navClass = fn (string ...$acts): string => in_array($currentAct, $acts, true) ? ' class="active"' : '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Đặt sân bóng 7 người') ?></title>
    <link rel="stylesheet" href="views/style.css">
</head>
<body>
    <header class="topbar">
        <div class="container nav">
            <a class="brand" href="index.php">⚽ Sân bóng 7 người</a>
            <nav class="menu">
                <a href="index.php"<?= $navClass('/') ?>>Trang chủ</a>
                <a href="index.php?act=booking"<?= $navClass('booking', 'booking-form') ?>>Đặt sân</a>
                <?php if ($authUser): ?>
                    <a href="index.php?act=my-bookings"<?= $navClass('my-bookings') ?>>Lịch của tôi</a>
                    <?php if (isAdmin()): ?>
                        <a href="index.php?act=admin"<?= $navClass('admin') ?>>Tổng quan</a>
                        <a href="index.php?act=admin-bookings"<?= $navClass('admin-bookings') ?>>Quản lý lịch</a>
                        <a href="index.php?act=courts"<?= $navClass('courts', 'court-form', 'court-save') ?>>Quản lý sân</a>
                        <a href="index.php?act=admin-users"<?= $navClass('admin-users') ?>>Khách hàng</a>
                    <?php endif; ?>
                    <span class="user-name"><?= e($authUser['full_name']) ?><?= isAdmin() ? ' (admin)' : '' ?></span>
                    <form method="post" action="index.php?act=logout" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <button type="submit" class="button button-small">Đăng xuất</button>
                    </form>
                <?php else: ?>
                    <a href="index.php?act=login"<?= $navClass('login') ?>>Đăng nhập</a>
                    <a href="index.php?act=register" class="button button-small button-accent">Đăng ký</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main<?= $fullWidth ? '' : ' class="container"' ?>>
        <?php if ($flash || !empty($pageError)): ?>
            <div<?= $fullWidth ? ' class="container notices"' : '' ?>>
                <?php if ($flash): ?>
                    <div class="notice notice-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
                <?php endif; ?>
                <?php if (!empty($pageError)): ?>
                    <div class="notice notice-error" role="alert"><?= e($pageError) ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>