<?php
/** @var array $court */
/** @var string $date */
/** @var int $hour */
/** @var int $maxHours */
/** @var array $user */
$title = 'Xác nhận đặt sân';
require __DIR__ . '/../layout/header.php';
 ?>
<section class="panel narrow">
    <h1>Xác nhận đặt sân</h1>
    <ul class="summary">
        <li><strong>Sân:</strong> <?= e($court['name']) ?> (<?= e($court['surface']) ?>)</li>
        <li><strong>Ngày:</strong> <?= e((new DateTimeImmutable($date))->format('d/m/Y')) ?></li>
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

        <div class="actions">
            <button class="button button-primary" type="submit">Xác nhận đặt sân</button>
            <a class="button" href="index.php?act=%2F&date=<?= e($date) ?>">Quay lại</a>
        </div>
    </form>
</section>
<script>
    (function () {
        var select = document.getElementById('hours');
        var total = document.getElementById('total');
        function update() {
            var money = parseInt(select.dataset.price, 10) * parseInt(select.value, 10);
            total.textContent = money.toLocaleString('vi-VN') + ' đ';
        }
        select.addEventListener('change', update);
        update();
    })();
</script>
<?php require __DIR__ . '/../layout/footer.php'; ?>