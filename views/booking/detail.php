<?php
/** @var array $booking */
/** @var array[] $payments */
$title = 'Chi tiết lịch đặt #' . $booking['id'];
require __DIR__ . '/../layout/header.php';

[$stateText, $stateClass] = bookingState($booking);
$paid = (float) $booking['paid_amount'];
$total = (float) $booking['total_price'];
$deposit = (float) $booking['deposit_amount'];
$remaining = remainingAmount($booking);
$depositDue = max(0.0, $deposit - $paid);
$transferCode = 'SAN' . $booking['id'];
$isPending = $booking['status'] === 'pending';
$isOwner = (int) $booking['user_id'] === (currentUser()['id'] ?? 0);
$backAct = isAdmin() ? 'admin-bookings' : 'my-bookings';
$qrUrl = '';
if (BANK_ID !== '' && $depositDue > 0) {
    $qrUrl = 'https://img.vietqr.io/image/' . rawurlencode(BANK_ID) . '-' . rawurlencode(BANK_ACCOUNT) . '-compact2.png?'
        . http_build_query(['amount' => (int) $depositDue, 'addInfo' => $transferCode, 'accountName' => BANK_ACCOUNT_NAME]);
}
?>
<section class="page-head">
    <div>
        <h1>Lịch đặt #<?= e($booking['id']) ?> <span class="status status-<?= e($stateClass) ?>"><?= e($stateText) ?></span></h1>
        <p class="muted">
            <?= e($booking['court_name']) ?> ·
            <?= e((new DateTimeImmutable($booking['start_at']))->format('d/m/Y')) ?> ·
            <?= e((new DateTimeImmutable($booking['start_at']))->format('H:i')) ?>–<?= e((new DateTimeImmutable($booking['end_at']))->format('H:i')) ?>
        </p>
    </div>
    <a class="button" href="index.php?act=<?= e($backAct) ?>">← Quay lại danh sách</a>
</section>

<div class="detail-grid">
    <div>
        <section class="panel">
            <h2>Thông tin thanh toán</h2>
            <dl class="info">
                <dt>Khách hàng</dt><dd><?= e($booking['customer_name']) ?> · <?= e($booking['phone']) ?></dd>
                <dt>Tổng tiền</dt><dd><strong><?= e(formatMoney($total)) ?></strong></dd>
                <dt>Tiền cọc yêu cầu</dt><dd><?= $deposit > 0 ? e(formatMoney($deposit)) . ' (' . (int) DEPOSIT_PERCENT . '%)' : 'Không yêu cầu cọc' ?></dd>
                <dt>Đã thanh toán</dt><dd class="money-paid"><?= e(formatMoney($paid)) ?></dd>
                <?php if ($booking['status'] !== 'cancelled'): ?>
                    <dt>Còn lại</dt><dd class="money-due"><?= e(formatMoney($remaining)) ?></dd>
                <?php endif; ?>
            </dl>
        </section>

        <section class="panel">
            <h2>Lịch sử thanh toán</h2>
            <?php if (!$payments): ?>
                <p class="empty-state">Chưa có khoản thanh toán nào.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Thời gian</th><th>Nội dung</th><th>Hình thức</th><th>Số tiền</th><th>Người ghi nhận</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= e((new DateTimeImmutable($payment['created_at']))->format('d/m/Y H:i')) ?></td>
                                    <td><?= $payment['type'] === 'refund' ? 'Hoàn tiền' : 'Thanh toán' ?><?= $payment['note'] ? ' · ' . e($payment['note']) : '' ?></td>
                                    <td><?= e(paymentMethodText($payment['method'])) ?></td>
                                    <td class="<?= (float) $payment['amount'] < 0 ? 'money-due' : 'money-paid' ?>"><?= e(formatMoney($payment['amount'])) ?></td>
                                    <td><?= e($payment['created_by_name'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <div>
        <?php if ($isPending): ?>
            <section class="panel pay-box">
                <h2>Đặt cọc để giữ chỗ</h2>
                <?php if ($booking['hold_until']): ?>
                    <p class="countdown-line">
                        Thời gian giữ chỗ còn lại:
                        <strong id="countdown" data-deadline="<?= e((new DateTimeImmutable($booking['hold_until']))->format('c')) ?>">--:--</strong>
                    </p>
                <?php endif; ?>
                <p>Số tiền cần chuyển: <strong class="money-due"><?= e(formatMoney($depositDue)) ?></strong></p>
                <table class="bank-table">
                    <tr><th>Ngân hàng</th><td><?= e(BANK_NAME) ?></td></tr>
                    <tr><th>Số tài khoản</th><td><?= e(BANK_ACCOUNT) ?></td></tr>
                    <tr><th>Chủ tài khoản</th><td><?= e(BANK_ACCOUNT_NAME) ?></td></tr>
                    <tr><th>Nội dung chuyển khoản</th><td><strong><?= e($transferCode) ?></strong></td></tr>
                </table>
                <?php if ($qrUrl !== ''): ?>
                    <p class="qr"><img src="<?= e($qrUrl) ?>" alt="Mã QR chuyển khoản" width="220"></p>
                <?php endif; ?>
                <p class="muted">Quá thời gian giữ chỗ mà chưa đặt cọc, lịch sẽ tự hủy và sân được mở lại cho người khác.</p>

                <?php if ($isOwner): ?>
                    <?php if ($booking['transfer_reported']): ?>
                        <p class="notice notice-success">Bạn đã báo chuyển khoản. Nhân viên đang kiểm tra và sẽ xác nhận sớm.</p>
                    <?php else: ?>
                        <form method="post" action="index.php?act=booking-report">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="booking_id" value="<?= e($booking['id']) ?>">
                            <button class="button button-primary" type="submit">Tôi đã chuyển khoản</button>
                        </form>
                    <?php endif; ?>
                <?php elseif ($booking['transfer_reported']): ?>
                    <p class="notice notice-success">Khách đã báo chuyển khoản, hãy kiểm tra tài khoản rồi ghi nhận khoản thu.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if ($booking['status'] === 'cancelled'): ?>
            <section class="panel">
                <h2>Thông tin hủy lịch</h2>
                <dl class="info">
                    <dt>Lý do</dt><dd><?= e($booking['cancel_reason'] ?? '—') ?></dd>
                    <?php if ($booking['cancelled_at']): ?>
                        <dt>Hủy lúc</dt><dd><?= e((new DateTimeImmutable($booking['cancelled_at']))->format('d/m/Y H:i')) ?></dd>
                    <?php endif; ?>
                    <?php if ((float) $booking['refund_amount'] > 0): ?>
                        <dt>Tiền hoàn lại</dt><dd><?= e(formatMoney($booking['refund_amount'])) ?>
                            (<?= $booking['refund_status'] === 'done' ? 'đã hoàn' : 'chờ hoàn' ?>)</dd>
                    <?php endif; ?>
                </dl>
                <?php if (isAdmin() && $booking['refund_status'] === 'pending'): ?>
                    <form method="post" action="index.php?act=refund-done" class="form">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="booking_id" value="<?= e($booking['id']) ?>">
                        <label for="refund_method">Hình thức hoàn tiền</label>
                        <select id="refund_method" name="method">
                            <option value="transfer">Chuyển khoản</option>
                            <option value="cash">Tiền mặt</option>
                        </select>
                        <button class="button button-primary" type="submit" onclick="return confirm('Xác nhận đã hoàn <?= e(formatMoney($booking['refund_amount'])) ?> cho khách?')">
                            Đã hoàn <?= e(formatMoney($booking['refund_amount'])) ?>
                        </button>
                    </form>
                <?php elseif (!isAdmin() && $booking['refund_status'] === 'pending'): ?>
                    <p class="muted">Nhân viên sẽ hoàn tiền cho bạn sớm. Cần hỗ trợ hãy liên hệ <?= e(SITE_PHONE) ?>.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if (isAdmin() && $booking['status'] !== 'cancelled' && $remaining > 0): ?>
            <section class="panel">
                <h2>Ghi nhận khoản thu</h2>
                <form method="post" action="index.php?act=payment-add" class="form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="booking_id" value="<?= e($booking['id']) ?>">

                    <label for="amount">Số tiền (đ)</label>
                    <input id="amount" name="amount" type="number" min="1000" step="1000" max="<?= (int) $remaining ?>"
                           value="<?= (int) ($isPending && $depositDue > 0 ? $depositDue : $remaining) ?>" required>

                    <label for="method">Hình thức</label>
                    <select id="method" name="method">
                        <option value="transfer">Chuyển khoản</option>
                        <option value="cash">Tiền mặt</option>
                    </select>

                    <label for="note">Ghi chú (không bắt buộc)</label>
                    <input id="note" name="note" type="text" maxlength="255" placeholder="Ví dụ: Cọc, thanh toán nốt tại sân">

                    <button class="button button-primary" type="submit"><?= $isPending ? 'Xác nhận đã nhận cọc' : 'Ghi nhận thanh toán' ?></button>
                </form>
            </section>
        <?php endif; ?>

        <?php if (canCancelBooking($booking) && ($isOwner || isAdmin())): ?>
            <section class="panel">
                <h2>Hủy lịch</h2>
                <p class="muted">
                    <?php if (isAdmin()): ?>
                        Admin hủy lịch sẽ hoàn lại toàn bộ số tiền khách đã trả.
                    <?php else: ?>
                        Hủy trước giờ đá từ <?= (int) CANCEL_FREE_HOURS ?> giờ trở lên được hoàn tiền; hủy sát giờ sẽ không hoàn tiền cọc.
                    <?php endif; ?>
                </p>
                <form method="post" action="index.php?act=booking-cancel" onsubmit="return confirm('Bạn có chắc muốn hủy lịch này?')">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <input type="hidden" name="booking_id" value="<?= e($booking['id']) ?>">
                    <input type="hidden" name="return" value="booking-detail">
                    <button class="button danger-button" type="submit">Hủy lịch đặt</button>
                </form>
            </section>
        <?php endif; ?>
    </div>
</div>

<?php if ($isPending && $booking['hold_until']): ?>
<script>
    (function () {
        var el = document.getElementById('countdown');
        if (!el) { return; }
        var deadline = new Date(el.dataset.deadline).getTime();
        function tick() {
            var left = Math.floor((deadline - Date.now()) / 1000);
            if (left <= 0) {
                el.textContent = 'Đã hết hạn';
                setTimeout(function () { location.reload(); }, 1500);
                return;
            }
            var m = Math.floor(left / 60), s = left % 60;
            el.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
            setTimeout(tick, 1000);
        }
        tick();
    })();
</script>
<?php endif; ?>
<?php require __DIR__ . '/../layout/footer.php'; ?>