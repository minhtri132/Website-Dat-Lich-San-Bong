<?php
/** @var array[] $users */
/** @var string $keyword */
/** @var ?string $pageError */
$title = 'Quản lý khách hàng';
require __DIR__ . '/../layout/header.php';
?>
<section class="page-head">
    <div>
        <h1>Khách hàng</h1>
        <p class="muted"><?= count($users) ?> khách hàng</p>
    </div>
</section>

<section class="panel">
    <form method="get" class="filter">
        <input type="hidden" name="act" value="admin-users">
        <label for="q">Tìm kiếm</label>
        <input id="q" type="search" name="q" value="<?= e($keyword) ?>" placeholder="Tên, số điện thoại hoặc email" style="min-width: 260px">
        <button class="button button-primary" type="submit">Tìm</button>
        <?php if ($keyword !== ''): ?>
            <a class="button" href="index.php?act=admin-users">Xóa tìm kiếm</a>
        <?php endif; ?>
    </form>

    <?php if (!$users): ?>
        <p class="empty-state">Không có khách hàng nào phù hợp.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Họ tên</th>
                        <th>Điện thoại</th>
                        <th>Email</th>
                        <th>Ngày đăng ký</th>
                        <th>Số lần đặt</th>
                        <th>Tổng tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= e($user['full_name']) ?></td>
                            <td><?= e($user['phone']) ?></td>
                            <td><?= e($user['email']) ?></td>
                            <td><?= e((new DateTimeImmutable($user['created_at']))->format('d/m/Y')) ?></td>
                            <td><?= (int) $user['booking_count'] ?></td>
                            <td><?= e(formatMoney($user['total_spent'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>