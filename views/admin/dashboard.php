<?php
/** @var array $stats */
/** @var array[] $chart */
/** @var array[] $upcoming */
/** @var ?string $pageError */
$title = 'Tổng quan quản trị';
require __DIR__ . '/../layout/header.php';
?>
<section class="page-head">
    <div>
        <h1>Tổng quan</h1>
        <p class="muted">Hôm nay, <?= e(date('d/m/Y')) ?></p>
    </div>
    <div class="date-nav">
        <a class="button button-small" href="index.php?act=admin-bookings">Quản lý lịch</a>
        <a class="button button-small" href="index.php?act=courts">Quản lý sân</a>
        <a class="button button-small" href="index.php?act=admin-users">Khách hàng</a>
    </div>
</section>

<section class="stats">
    <div class="stat">
        <span class="stat-label">Lịch hôm nay</span>
        <strong><?= (int) $stats['today_count'] ?></strong>
    </div>
    <div class="stat">
        <span class="stat-label">Tổng tiền hôm nay</span>
        <strong><?= e(formatMoney($stats['today_revenue'])) ?></strong>
    </div>
    <div class="stat">
        <span class="stat-label">Lịch tháng <?= e(date('m/Y')) ?></span>
        <strong><?= (int) $stats['month_count'] ?></strong>
    </div>
    <div class="stat">
        <span class="stat-label">Tổng tiền tháng này</span>
        <strong><?= e(formatMoney($stats['month_revenue'])) ?></strong>
    </div>
    <div class="stat">
        <span class="stat-label">Sân đang hoạt động</span>
        <strong><?= (int) $stats['courts_active'] ?>/<?= (int) $stats['courts_total'] ?></strong>
    </div>
    <div class="stat">
        <span class="stat-label">Khách hàng</span>
        <strong><?= (int) $stats['customers'] ?></strong>
    </div>
</section>

<section class="panel">
    <h2>Tổng tiền theo ngày <span class="muted">(3 ngày trước, hôm nay và 3 ngày tới)</span></h2>
    <?php if (!$chart): ?>
        <p class="empty-state">Chưa có dữ liệu.</p>
    <?php else: ?>
        <div class="chart">
            <?php foreach ($chart as $bar): ?>
                <div class="chart-col<?= $bar['is_today'] ? ' chart-today' : '' ?>">
                    <span class="chart-value"><?= $bar['value'] > 0 ? e(number_format($bar['value'] / 1000, 0, ',', '.')) . 'k' : '0' ?></span>
                    <div class="chart-bar" style="height: <?= (int) $bar['percent'] ?>%"></div>
                    <span class="chart-label"><?= e($bar['label']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="panel">
    <h2>Lịch sắp diễn ra</h2>
    <?php if (!$upcoming): ?>
        <p class="empty-state">Chưa có lịch nào sắp tới.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Ngày</th>
                        <th>Giờ</th>
                        <th>Sân</th>
                        <th>Khách hàng</th>
                        <th>Điện thoại</th>
                        <th>Tổng tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcoming as $booking): ?>
                        <tr>
                            <td><?= e((new DateTimeImmutable($booking['start_at']))->format('d/m/Y')) ?></td>
                            <td><?= e((new DateTimeImmutable($booking['start_at']))->format('H:i')) ?>–<?= e((new DateTimeImmutable($booking['end_at']))->format('H:i')) ?></td>
                            <td><?= e($booking['court_name']) ?></td>
                            <td><?= e($booking['customer_name']) ?></td>
                            <td><?= e($booking['phone']) ?></td>
                            <td><?= e(formatMoney($booking['total_price'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>