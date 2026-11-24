<?php
/** @var string[] $errors */
/** @var array $old */
$title = 'Đăng ký';
require __DIR__ . '/../layout/header.php';
?>
<section class="panel auth-panel">
    <h1>Tạo tài khoản khách hàng</h1>
    <p class="muted">Dùng thông tin chính xác để đặt sân và nhận thông báo.</p>

    <?php foreach ($errors as $error): ?>
        <div class="notice notice-error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post" action="index.php?act=register" class="form">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

        <label for="full_name">Họ và tên</label>
        <input id="full_name" name="full_name" type="text" maxlength="100"
               value="<?= e($old['full_name']) ?>" autocomplete="name" required autofocus>

        <label for="phone">Số điện thoại</label>
        <input id="phone" name="phone" type="tel" maxlength="20"
               value="<?= e($old['phone']) ?>" autocomplete="tel" placeholder="0901234567" required>

        <label for="email">Email</label>
        <input id="email" name="email" type="email" maxlength="150"
               value="<?= e($old['email']) ?>" autocomplete="email" required>

        <label for="password">Mật khẩu</label>
        <input id="password" name="password" type="password"
               minlength="6" autocomplete="new-password" required>

        <label for="password_confirm">Nhập lại mật khẩu</label>
        <input id="password_confirm" name="password_confirm" type="password"
               minlength="6" autocomplete="new-password" required>

        <button class="button button-primary" type="submit">Đăng ký</button>
    </form>

    <p class="muted">Đã có tài khoản?
        <a href="index.php?act=login">Đăng nhập</a>
    </p>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>
