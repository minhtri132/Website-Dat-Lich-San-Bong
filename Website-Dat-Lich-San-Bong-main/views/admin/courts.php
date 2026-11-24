<?php
/** @var array[] $courts */
/** @var ?string $pageError */
$title = 'Quản lý sân';
require __DIR__ . '/../layout/header.php'; ?>
<section class="page-head">
    <div>
        <h1>Quản lý sân</h1>
        <p class="muted"><?= count($courts) ?> sân</p>
    </div>
    <a class="button button-primary" href="index.php?act=court-form">+ Thêm sân</a>
</section>

<section class="panel">
    <?php if (!$courts): ?>
        <p class="empty-state">Chưa có sân nào.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tên sân</th>
                        <th>Mặt sân</th>
                        <th>Giá/giờ</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courts as $court): ?>
                        <tr>
                            <td><?= e($court['name']) ?></td>
                            <td><?= e($court['surface']) ?></td>
                            <td><?= e(formatMoney($court['price_per_hour'])) ?></td>
                            <td>
                                <span class="status status-<?= $court['is_active'] ? 'confirmed' : 'cancelled' ?>">
                                    <?= $court['is_active'] ? 'Hoạt động' : 'Ngừng hoạt động' ?>
                                </span>
                            </td>
                            <td class="row-actions">
                                <a class="button-link" href="index.php?act=court-form&id=<?= e($court['id']) ?>">Sửa</a>
                                <form method="post" action="index.php?act=court-toggle">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= e($court['id']) ?>">
                                    <button class="button-link" type="submit"><?= $court['is_active'] ? 'Ngừng' : 'Bật lại' ?></button>
                                </form>
                                <form method="post" action="index.php?act=court-delete" onsubmit="return confirm('Xóa sân này?')">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= e($court['id']) ?>">
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