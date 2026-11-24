<?php
/** @var array[] $courts */
/** @var array<int, int> $freeSlots */
/** @var ?float $minPrice */
/** @var ?string $pageError */
$title = 'Đặt sân bóng 7 người';
$fullWidth = true;
require __DIR__ . '/layout/header.php';
?>
<section class="hero">
    <div class="container hero-inner">
        <p class="eyebrow">SÂN BÓNG ĐÁ MINI 7 NGƯỜI</p>
        <h1>Đặt sân bóng nhanh chóng, không lo trùng lịch</h1>
        <p class="hero-text">
            Xem giờ trống ngay trên trang, chọn sân và giờ đá chỉ trong vài bước.
            Mở cửa mỗi ngày từ <?= sprintf('%02d:00', OPEN_HOUR) ?> đến <?= sprintf('%02d:00', CLOSE_HOUR) ?>.
        </p>
        <div class="hero-actions">
            <a class="button button-large button-accent" href="index.php?act=booking">Đặt sân ngay</a>
            <a class="button button-large button-ghost" href="#courts">Xem các sân</a>
        </div>
        <ul class="hero-stats">
            <li><strong><?= count($courts) ?></strong><span>sân đang hoạt động</span></li>
            <li><strong><?= sprintf('%02d:00–%02d:00', OPEN_HOUR, CLOSE_HOUR) ?></strong><span>mở cửa mỗi ngày</span></li>
            <?php if ($minPrice !== null): ?>
                <li><strong><?= e(formatMoney($minPrice)) ?></strong><span>giá thuê từ / giờ</span></li>
            <?php endif; ?>
        </ul>
    </div>
</section>

<section class="section" id="courts">
    <div class="container">
        <h2 class="section-title">Các sân của chúng tôi</h2>
        <p class="section-sub">Số khung giờ còn trống được cập nhật theo thời gian thực cho hôm nay.</p>

        <?php if (!$courts): ?>
            <p class="empty-state">Chưa có sân hoạt động. Hãy chạy <code>database.sql</code> hoặc nhờ admin thêm sân.</p>
        <?php else: ?>
            <div class="court-grid">
                <?php foreach ($courts as $court): ?>
                    <?php $free = $freeSlots[$court['id']] ?? 0; ?>
                    <article class="court-card">
                        <div class="pitch" aria-hidden="true"></div>
                        <div class="court-body">
                            <h3><?= e($court['name']) ?></h3>
                            <p class="muted"><?= e($court['surface']) ?> · 7 người</p>
                            <p class="court-price"><strong><?= e(formatMoney($court['price_per_hour'])) ?></strong> / giờ</p>
                            <p class="court-free <?= $free > 0 ? 'has-free' : 'no-free' ?>">
                                <?= $free > 0 ? 'Còn ' . (int) $free . ' khung giờ trống hôm nay' : 'Hôm nay đã hết giờ trống' ?>
                            </p>
                            <a class="button button-primary" href="index.php?act=booking">Xem giờ trống</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <h2 class="section-title">Đặt sân chỉ với 3 bước</h2>
        <div class="steps">
            <div class="step">
                <span class="step-number">1</span>
                <h3>Chọn giờ trống</h3>
                <p>Mở bảng lịch, chọn ngày và bấm vào ô còn trống của sân bạn muốn.</p>
            </div>
            <div class="step">
                <span class="step-number">2</span>
                <h3>Xác nhận thông tin</h3>
                <p>Chọn số giờ (tối đa <?= (int) MAX_HOURS ?> giờ), kiểm tra tổng tiền rồi xác nhận đặt.</p>
            </div>
            <div class="step">
                <span class="step-number">3</span>
                <h3>Ra sân thi đấu</h3>
                <p>Xem lại lịch trong mục "Lịch của tôi". Cần đổi kế hoạch thì hủy trước giờ đá.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <h2 class="section-title">Vì sao chọn chúng tôi</h2>
        <div class="features">
            <div class="feature">
                <span class="feature-icon">🕒</span>
                <h3>Đặt trực tuyến mọi lúc</h3>
                <p>Xem lịch và đặt sân bất kỳ lúc nào, không cần gọi điện.</p>
            </div>
            <div class="feature">
                <span class="feature-icon">💰</span>
                <h3>Giá rõ ràng</h3>
                <p>Tổng tiền hiện ngay khi chọn số giờ, giá được lưu đúng lúc bạn đặt.</p>
            </div>
            <div class="feature">
                <span class="feature-icon">✅</span>
                <h3>Không trùng lịch</h3>
                <p>Hệ thống tự kiểm tra, mỗi khung giờ chỉ một đội được đặt.</p>
            </div>
            <div class="feature">
                <span class="feature-icon">↩️</span>
                <h3>Hủy lịch dễ dàng</h3>
                <p>Quản lý và hủy lịch của bạn ngay trong tài khoản, trước giờ đá.</p>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="cta">
            <?php if (currentUser()): ?>
                <h2>Sẵn sàng ra sân chưa?</h2>
                <p>Chọn khung giờ phù hợp và giữ chỗ cho đội của bạn ngay bây giờ.</p>
                <a class="button button-large button-accent" href="index.php?act=booking">Đặt sân ngay</a>
            <?php else: ?>
                <h2>Tạo tài khoản để bắt đầu đặt sân</h2>
                <p>Đăng ký miễn phí chỉ mất một phút, sau đó bạn đặt sân và quản lý lịch của mình dễ dàng.</p>
                <div class="hero-actions center-actions">
                    <a class="button button-large button-accent" href="index.php?act=register">Đăng ký miễn phí</a>
                    <a class="button button-large button-ghost" href="index.php?act=login">Đăng nhập</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require __DIR__ . '/layout/footer.php'; ?>