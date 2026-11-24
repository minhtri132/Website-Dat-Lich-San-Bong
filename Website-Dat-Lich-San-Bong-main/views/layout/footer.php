    </main>
    <footer class="site-footer">
        <div class="container footer-grid">
            <div>
                <p class="footer-brand">⚽ Sân bóng 7 người</p>
                <p>Đặt sân trực tuyến nhanh chóng, rõ ràng, không trùng lịch.</p>
            </div>
            <div>
                <p class="footer-title">Giờ mở cửa</p>
                <p>Mỗi ngày, <?= sprintf('%02d:00 – %02d:00', OPEN_HOUR, CLOSE_HOUR) ?></p>
            </div>
            <div>
                <p class="footer-title">Liên hệ</p>
                <?php if (defined('SITE_PHONE')): ?><p>Điện thoại: <?= e(SITE_PHONE) ?></p><?php endif; ?>
                <?php if (defined('SITE_ADDRESS')): ?><p>Địa chỉ: <?= e(SITE_ADDRESS) ?></p><?php endif; ?>
            </div>
        </div>
        <p class="footer-copy">© <?= e(date('Y')) ?> Đặt sân bóng 7 người · Múi giờ Việt Nam (UTC+7)</p>
    </footer>
</body>
</html>