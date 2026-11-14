<?php
/** @var string $date */
/** @var array[] $courts */
/** @var array $cells */
/** @var string $prevDate */
/** @var string $nextDate */
/** @var ?string $pageError */
$title = 'Đặt sân bóng 7 người';
require __DIR__ . '/../layout/header.php';
 ?>
<section class="page-head">
    <div>
        <h1>Đặt sân bóng 7 người</h1>
        <p class="muted">Chọn một ô trống trong bảng để đặt sân. Giờ nhận đặt từ <?= sprintf('%02d:00', OPEN_HOUR) ?> đến <?= sprintf('%02d:00', CLOSE_HOUR) ?>.</p>
    </div>
        <div class="date-nav">
        <a class="button button-small" href="index.php?act=booking&date=<?= e($prevDate) ?>">← Hôm trước</a>
        <a class="button button-small" href="index.php?act=booking">Hôm nay</a>
        <a class="button button-small" href="index.php?act=booking&date=<?= e($nextDate) ?>">Hôm sau →</a>
        <form method="get" class="inline-form">
            <input type="hidden" name="act" value="booking">
            <input type="date" name="date" value="<?= e($date) ?>" required>
            <button type="submit" class="button button-small button-primary">Xem</button>
        </form>
    </div>
</section>

<section class="panel">
    <h2>Lịch sân ngày <?= e((new DateTimeImmutable($date))->format('d/m/Y')) ?></h2>
    <div class="legend">
        <span class="slot slot-free">Trống</span>
        <span class="slot slot-booked">Đã đặt</span>
        <span class="slot slot-mine">Của bạn</span>
        <span class="slot slot-past">Đã qua</span>
    </div>

    <?php if (!$courts): ?>
        <p class="empty-state">Chưa có sân hoạt động. Hãy chạy <code>database.sql</code> hoặc nhờ admin thêm sân.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="grid">
                <thead>
                    <tr>
                        <th>Giờ</th>
                        <?php foreach ($courts as $court): ?>
                            <th>
                                <?= e($court['name']) ?>
                                <small><?= e($court['surface']) ?> · <?= e(formatMoney($court['price_per_hour'])) ?>/giờ</small>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($hour = OPEN_HOUR; $hour < CLOSE_HOUR; $hour++): ?>
                        <tr>
                            <th class="hour"><?= sprintf('%02d:00–%02d:00', $hour, $hour + 1) ?></th>
                            <?php foreach ($courts as $court): ?>
                                <?php $cell = $cells[$hour][$court['id']]; ?>
                                <td>
                                    <?php if ($cell['state'] === 'free'): ?>
                                        <a class="slot slot-free" href="index.php?<?= e(http_build_query(['act' => 'booking-form', 'court_id' => $court['id'], 'date' => $date, 'hour' => $hour])) ?>">Đặt</a>
                                    <?php elseif ($cell['state'] === 'past'): ?>
                                        <span class="slot slot-past">—</span>
                                    <?php else: ?>
                                        <span class="slot slot-<?= e($cell['state']) ?>"><?= e($cell['label']) ?></span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../layout/footer.php'; ?>