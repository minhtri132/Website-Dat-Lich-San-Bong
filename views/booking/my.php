<?php
/** @var array[] $bookings */
/** @var ?string $pageError */
$title = 'Lịch của tôi';
require __DIR__ . '/../layout/header.php';
 ?>
<section class="page-head">
    <div>
        <h1>Lịch đặt sân của tôi</h1>
        <p class="muted"><?= count($bookings) ?> lịch đặt</p>
    </div>
    <a class="button button-primary" href="index.php">+ Đặt sân mới</a>
</section>

<section class="panel">
    <?php if (!$bookings): ?>
        <p class="empty-state">Bạn chưa đặt sân nào.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Ngày</th>
                        <th>Giờ</th>
                        <th>Sân</th>
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
                            <td><?= e(formatMoney($booking['total_price'])) ?></td>
                            <td><span class="status status-<?= e(bookingStatusClass($booking)) ?>"><?= e(bookingStatusText($booking)) ?></span></td>
                            <td>
                                <?php if (canCancelBooking($booking)): ?>
                                    <form method="post" action="index.php?act=booking-cancel" onsubmit="return confirm('Bạn có chắc muốn hủy lịch này?')">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="booking_id" value="<?= e($booking['id']) ?>">
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