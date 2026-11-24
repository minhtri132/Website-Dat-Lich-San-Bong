<?php
/** @var array $extra */
/** @var string[] $errors */
$isEdit = $extra['id'] !== '';
$title = $isEdit ? 'Sửa option' : 'Thêm option';
require __DIR__ . '/../layout/header.php';
?>
<section class="panel narrow">
    <h1><?= $isEdit ? 'Sửa option' : 'Thêm option mới' ?></h1>
    <?php foreach ($errors as $error): ?>
        <div class="notice notice-error"><?= e($error) ?></div>
    <?php endforeach; ?>
    <form method="post" action="index.php?act=extra-save" class="form">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="id" value="<?= e($extra['id']) ?>">

        <label for="category">Nhóm option</label>
        <select id="category" name="category" required>
            <option value="water" <?= $extra['category'] === 'water' ? 'selected' : '' ?>>Nước</option>
            <option value="gloves" <?= $extra['category'] === 'gloves' ? 'selected' : '' ?>>Găng bắt gôn</option>
            <option value="shoes" <?= $extra['category'] === 'shoes' ? 'selected' : '' ?>>Giày đá</option>
        </select>

        <label for="name">Tên cụ thể</label>
        <input id="name" name="name" type="text" maxlength="100"
               value="<?= e($extra['name']) ?>"
               placeholder="Ví dụ: Nước Aquafina 500ml, Giày Nike size 42, Găng Adidas" required>

        <label for="price">Giá bán (đ)</label>
        <input id="price" name="price" type="number" min="1000" step="1000"
               value="<?= e($extra['price']) ?>" required>

        <label class="checkbox">
            <input type="checkbox" name="is_active" value="1" <?= $extra['is_active'] ? 'checked' : '' ?>>
            Đang bán
        </label>

        <div class="actions">
            <button class="button button-primary" type="submit">Lưu option</button>
            <a class="button" href="index.php?act=extras">Quay lại</a>
        </div>
    </form>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>
