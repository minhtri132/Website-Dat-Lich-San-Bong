<?php
/** @var array $bookings */
/** @var ?string $date */
/** @var ?string $status */
/** @var int $count */
/** @var float $revenue */
$title = 'Quản lý lịch đặt';
require __DIR__ . '/../layout/header.php';
?>
<section class="page-head">
    <div>
        <h1>Quản lý lịch đặt</h1>
        <p class="muted">Xem và hủy lịch đặt của tất cả khách hàng.</p>
    </div>
</section>

<section class="stats">
    <div class="stat"><span class="stat-label">Số lịch đang hiệu lực</span><strong><?= (int) $count ?></strong></div>
    <div class="stat"><span class="stat-label">Tổng tiền các lịch này</span><strong><?= e(formatMoney($revenue)) ?></strong></div>
</section>

<section class="panel">
    <form method="get" class="filter">
        <input type="hidden" name="act" value="admin-bookings">
        <label for="date">Ngày</label>
        <input id="date" type="date" name="date" value="<?= e($date ?? '') ?>">
        <label for="status">Trạng thái</label>
        <select id="status" name="status">
            <option value="">Tất cả</option>
            <option value="confirmed" <?= ($status ?? '') === 'confirmed' ? 'selected' : '' ?>>Đã đặt</option>
            <option value="cancelled" <?= ($status ?? '') === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
        </select>
        <button class="button button-primary" type="submit">Lọc</button>
        <a class="button" href="index.php?act=admin-bookings&date=">Xem tất cả ngày</a>
    </form>

    <?php if (!$bookings): ?>
        <p class="empty-state">Không có lịch đặt nào phù hợp.</p>
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
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td><?= e((new DateTimeImmutable($booking['start_at']))->format('d/m/Y')) ?></td>
                            <td><?= e((new DateTimeImmutable($booking['start_at']))->format('H:i')) ?>–<?= e((new DateTimeImmutable($booking['end_at']))->format('H:i')) ?></td>
                            <td><?= e($booking['court_name']) ?></td>
                            <td><?= e($booking['customer_name']) ?></td>
                            <td><?= e($booking['phone']) ?></td>
                            <td><?= e(formatMoney($booking['total_price'])) ?></td>
                            <td><span class="status status-<?= e(bookingStatusClass($booking)) ?>"><?= e(bookingStatusText($booking)) ?></span></td>
                            <td>
                                <?php if (canCancelBooking($booking)): ?>
                                    <form method="post" action="index.php?act=booking-cancel" onsubmit="return confirm('Hủy lịch đặt này?')">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="booking_id" value="<?= e($booking['id']) ?>">
                                        <input type="hidden" name="return" value="admin-bookings">
                                        <input type="hidden" name="filter_date" value="<?= e($date ?? '') ?>">
                                        <input type="hidden" name="filter_status" value="<?= e($status ?? '') ?>">
                                        <button class="button-link" type="submit">Hủy lịch</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>