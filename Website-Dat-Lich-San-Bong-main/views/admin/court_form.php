<?php
/** @var array $court */
/** @var string[] $errors */
$isEdit = $court['id'] !== '';
$title = $isEdit ? 'Sửa sân' : 'Thêm sân';
require __DIR__ . '/../layout/header.php';
?>
<section class="panel narrow">
    <h1><?= $isEdit ? 'Sửa sân' : 'Thêm sân mới' ?></h1>
    <?php foreach ($errors as $error): ?>
        <div class="notice notice-error"><?= e($error) ?></div>
    <?php endforeach; ?>
    <form method="post" action="index.php?act=court-save" class="form">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="id" value="<?= e($court['id']) ?>">

        <label for="name">Tên sân</label>
        <input id="name" name="name" type="text" maxlength="100" value="<?= e($court['name']) ?>" required>

        <label for="surface">Loại mặt sân</label>
        <input id="surface" name="surface" type="text" maxlength="100" value="<?= e($court['surface']) ?>" required>

        <label for="price_per_hour">Giá thuê mỗi giờ (đ)</label>
        <input id="price_per_hour" name="price_per_hour" type="number" min="1000" step="1000" value="<?= e($court['price_per_hour']) ?>" required>

        <label class="checkbox">
            <input type="checkbox" name="is_active" value="1" <?= $court['is_active'] ? 'checked' : '' ?>>
            Đang hoạt động (cho phép đặt)
        </label>

        <div class="actions">
            <button class="button button-primary" type="submit">Lưu</button>
            <a class="button" href="index.php?act=courts">Quay lại</a>
        </div>
    </form>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>