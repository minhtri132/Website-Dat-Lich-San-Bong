<?php
/** @var array[] $extras */
/** @var ?string $pageError */
$title = 'Quản lý option';
require __DIR__ . '/../layout/header.php';

$categoryNames = [
    'water' => 'Nước',
    'gloves' => 'Găng bắt gôn',
    'shoes' => 'Giày đá',
];
?>
<section class="page-head">
    <div>
        <h1>Quản lý option</h1>
        <p class="muted"><?= count($extras) ?> mặt hàng dịch vụ</p>
    </div>
    <a class="button button-primary" href="index.php?act=extra-form">+ Thêm option</a>
</section>

<section class="panel">
    <?php if (!$extras): ?>
        <p class="empty-state">Chưa có option nào.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Loại</th>
                        <th>Tên cụ thể</th>
                        <th>Giá</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($extras as $extra): ?>
                        <tr>
                            <td><?= e($categoryNames[$extra['category']] ?? $extra['category']) ?></td>
                            <td><?= e($extra['name']) ?></td>
                            <td><?= e(formatMoney($extra['price'])) ?></td>
                            <td>
                                <span class="status status-<?= $extra['is_active'] ? 'confirmed' : 'cancelled' ?>">
                                    <?= $extra['is_active'] ? 'Đang bán' : 'Ngừng hoạt động' ?>
                                </span>
                            </td>
                            <td class="row-actions">
                                <a class="button-link" href="index.php?act=extra-form&id=<?= e($extra['id']) ?>">Sửa</a>
                                <form method="post" action="index.php?act=extra-toggle">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= e($extra['id']) ?>">
                                    <button class="button-link" type="submit"><?= $extra['is_active'] ? 'Ngừng' : 'Bật lại' ?></button>
                                </form>
                                <form method="post" action="index.php?act=extra-delete" onsubmit="return confirm('Xóa option này?')">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= e($extra['id']) ?>">
                                    <button class="button-link danger" type="submit">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>
