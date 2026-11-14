<?php
/** @var array[] $bookings */
/** @var ?string $date */
/** @var ?string $status */
/** @var int $count */
/** @var float $total */
/** @var float $collected */
/** @var float $outstanding */
/** @var ?string $pageError */
$title = 'Quản lý lịch đặt';
require __DIR__ . '/../layout/header.php';
?>
<section class="page-head">
    <div>
        <h1>Quản lý lịch đặt</h1>
        <p class="muted">Theo dõi đặt cọc, thanh toán và hoàn tiền của khách hàng.</p>
    </div>
</section>

<section class="stats">
    <div class="stat"><span class="stat-label">Lịch đang giữ sân</span><strong><?= (int) $count ?></strong></div>
    <div class="stat"><span class="stat-label">Tổng giá trị các lịch</span><strong><?= e(formatMoney($total)) ?></strong></div>
    <div class="stat"><span class="stat-label">Đã thu</span><strong><?= e(formatMoney($collected)) ?></strong></div>
    <div class="stat"><span class="stat-label">Còn phải thu</span><strong><?= e(formatMoney($outstanding)) ?></strong></div>
</section>

<section class="panel">
    <form method="get" class="filter">
        <input type="hidden" name="act" value="admin-bookings">
        <label for="date">Ngày</label>
        <input id="date" type="date" name="date" value="<?= e($date ?? '') ?>">
        <label for="status">Trạng thái</label>
        <select id="status" name="status">
            <option value="">Tất cả</option>
            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Chờ đặt cọc</option>
            <option value="confirmed" <?= $status === 'confirmed' ? 'selected' : '' ?>>Đã xác nhận</option>
            <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
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
                        <th>Tổng tiền</th>
                        <th>Đã thu</th>
                        <th>Còn lại</th>
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
                            <td><?= e($booking['customer_name']) ?><br><small class="muted"><?= e($booking['phone']) ?></small></td>
                            <td><?= e(formatMoney($booking['total_price'])) ?></td>
                            <td><?= e(formatMoney($booking['paid_amount'])) ?></td>
                            <td><?= $booking['status'] === 'cancelled' ? '—' : e(formatMoney(remainingAmount($booking))) ?></td>
                            <td>
                                <span class="status status-<?= e(bookingStatusClass($booking)) ?>"><?= e(bookingStatusText($booking)) ?></span>
                                <?php if ($booking['status'] === 'pending' && $booking['transfer_reported']): ?>
                                    <small class="flag">Khách báo đã chuyển khoản</small>
                                <?php endif; ?>
                                <?php if ($booking['status'] === 'cancelled' && $booking['refund_status'] === 'pending'): ?>
                                    <small class="flag">Cần hoàn <?= e(formatMoney($booking['refund_amount'])) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="row-actions">
                                <a class="button-link" href="index.php?act=booking-detail&id=<?= e($booking['id']) ?>">Chi tiết</a>
                                <?php if (canCancelBooking($booking)): ?>
                                    <form method="post" action="index.php?act=booking-cancel" onsubmit="return confirm('Hủy lịch đặt này? Khách sẽ được hoàn lại toàn bộ số tiền đã trả.')">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="booking_id" value="<?= e($booking['id']) ?>">
                                        <input type="hidden" name="return" value="admin-bookings">
                                        <input type="hidden" name="filter_date" value="<?= e($date ?? '') ?>">
                                        <input type="hidden" name="filter_status" value="<?= e($status ?? '') ?>">
                                        <button class="button-link danger" type="submit">Hủy</button>
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