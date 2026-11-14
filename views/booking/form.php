<?php
/** @var array $court */
/** @var string $date */
/** @var int $hour */
/** @var int $maxHours */
/** @var array $user */
$title = 'Xác nhận đặt sân';
$bookingDate = new DateTimeImmutable($date);
require __DIR__ . '/../layout/header.php';
?>
<section class="panel narrow">
    <h1>Xác nhận đặt sân</h1>
    <ul class="summary">
        <li><strong>Sân:</strong> <?= e($court['name']) ?> (<?= e($court['surface']) ?>)</li>
        <li><strong>Ngày:</strong> <?= e($bookingDate->format('d/m/Y')) ?></li>
        <li><strong>Bắt đầu lúc:</strong> <?= sprintf('%02d:00', $hour) ?></li>
        <li><strong>Giá thuê:</strong> <?= e(formatMoney($court['price_per_hour'])) ?>/giờ</li>
    </ul>

    <form method="post" action="index.php?act=booking-create" class="form">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="court_id" value="<?= e($court['id']) ?>">
        <input type="hidden" name="date" value="<?= e($date) ?>">
        <input type="hidden" name="hour" value="<?= e($hour) ?>">

        <label for="hours">Thời lượng</label>
        <select id="hours" name="hours" data-price="<?= e((int) $court['price_per_hour']) ?>" data-start="<?= e($hour) ?>">
            <?php for ($n = 1; $n <= $maxHours; $n++): ?>
                <option value="<?= $n ?>"><?= $n ?> giờ (đến <?= sprintf('%02d:00', $hour + $n) ?>)</option>
            <?php endfor; ?>
        </select>

        <label for="customer_name">Tên khách hàng</label>
        <input id="customer_name" name="customer_name" type="text" maxlength="100" value="<?= e($user['full_name']) ?>" required>

        <label for="phone">Số điện thoại</label>
        <input id="phone" name="phone" type="tel" maxlength="20" value="<?= e($user['phone']) ?>" required>

        <p class="total">Tổng tiền: <strong id="total"><?= e(formatMoney($court['price_per_hour'])) ?></strong></p>
        <?php if (DEPOSIT_PERCENT > 0): ?>
            <p class="deposit-note">
                Đặt cọc <?= (int) DEPOSIT_PERCENT ?>%: <strong id="deposit"><?= e(formatMoney(depositFor((float) $court['price_per_hour']))) ?></strong>.
                Sau khi xác nhận, sân được giữ cho bạn <?= (int) HOLD_MINUTES ?> phút để chuyển cọc; quá hạn lịch sẽ tự hủy.
            </p>
            <p class="muted">Hủy trước giờ đá từ <?= (int) CANCEL_FREE_HOURS ?> giờ trở lên được hoàn tiền; hủy sát giờ sẽ không hoàn tiền cọc.</p>
        <?php endif; ?>

        <div class="actions">
            <button class="button button-primary" type="submit">Xác nhận đặt sân</button>
            <a class="button" href="index.php?act=booking&date=<?= e($date) ?>">Quay lại</a>
        </div>
    </form>
</section>
<script>
    (function () {
        var select = document.getElementById('hours');
        var total = document.getElementById('total');
        var depositEl = document.getElementById('deposit');
        var depositPercent = <?= (int) DEPOSIT_PERCENT ?>;

        function format(money) {
            return money.toLocaleString('vi-VN') + ' đ';
        }

        function update() {
            var money = parseInt(select.dataset.price, 10) * parseInt(select.value, 10);
            total.textContent = format(money);
            if (depositEl) {
                // Làm tròn lên đến nghìn đồng, giống hàm depositFor() ở phía server
                depositEl.textContent = format(Math.min(money, Math.ceil(money * depositPercent / 100 / 1000) * 1000));
            }
        }

        select.addEventListener('change', update);
        update();
    })();
</script>
<?php require __DIR__ . '/../layout/footer.php'; ?>