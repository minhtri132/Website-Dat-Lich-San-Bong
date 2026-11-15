<?php
/** @var string[] $errors */
/** @var array $old */
$title = 'Đăng ký';
require __DIR__ . '/../layout/header.php';
?>
<section class="panel narrow">
    <h1>Đăng ký tài khoản</h1>
    <?php foreach ($errors as $error): ?>
        <div class="notice notice-error" role="alert"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post" action="index.php?act=register" class="form">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

        <label for="full_name">Họ tên</label>
        <input id="full_name" name="full_name" type="text" maxlength="100" value="<?= e($old['full_name']) ?>" autocomplete="name" required>

        <label for="phone">Số điện thoại</label>
        <input id="phone" name="phone" type="tel" maxlength="20" value="<?= e($old['phone']) ?>" autocomplete="tel" required>

        <label for="email">Email</label>
        <input id="email" name="email" type="email" maxlength="150" value="<?= e($old['email']) ?>" autocomplete="email" required>

        <label for="password">Mật khẩu (ít nhất 6 ký tự)</label>
        <input id="password" name="password" type="password" minlength="6" autocomplete="new-password" required>

        <label for="password_confirm">Nhập lại mật khẩu</label>
        <input id="password_confirm" name="password_confirm" type="password" minlength="6" autocomplete="new-password" required>

        <button class="button button-primary" type="submit">Tạo tài khoản</button>
    </form>

    <p>Đã có tài khoản? <a href="index.php?act=login">Đăng nhập</a>.</p>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>
