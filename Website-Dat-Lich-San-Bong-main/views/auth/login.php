<?php
/** @var string[] $errors */
/** @var string $email */
$title = 'Đăng nhập';
require __DIR__ . '/../layout/header.php';
?>
<section class="panel auth-panel">
    <h1>Đăng nhập</h1>
    <p class="muted">Nhập email và mật khẩu đã đăng ký.</p>

    <?php foreach ($errors as $error): ?>
        <div class="notice notice-error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post" action="index.php?act=login" class="form">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

        <label for="email">Email</label>
        <input id="email" name="email" type="email" maxlength="150"
               value="<?= e($email) ?>" autocomplete="email" required autofocus>

        <label for="password">Mật khẩu</label>
        <input id="password" name="password" type="password"
               minlength="6" autocomplete="current-password" required>

        <button class="button button-primary" type="submit">Đăng nhập</button>
    </form>

    <p class="muted">Chưa có tài khoản?
        <a href="index.php?act=register">Đăng ký ngay</a>
    </p>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>
