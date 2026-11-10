<?php
// Trang chủ giới thiệu: banner, danh sách sân, hướng dẫn đặt sân
class HomeController
{
    public function index(): void
    {
        $courts = [];
        $freeSlots = [];
        $minPrice = null;
        $pageError = null;

        try {
            $courts = (new CourtModel())->getActive();
            $bookings = (new BookingModel())->getBookingsByDate(date('Y-m-d'));

            // Các giờ đã có người đặt hôm nay, theo từng sân
            $booked = [];
            foreach ($bookings as $booking) {
                $startHour = (int) (new DateTimeImmutable($booking['start_at']))->format('G');
                $endHour = (int) (new DateTimeImmutable($booking['end_at']))->format('G');
                for ($hour = $startHour; $hour < $endHour; $hour++) {
                    $booked[$booking['court_id']][$hour] = true;
                }
            }

            // Đếm số khung giờ còn trống từ bây giờ đến lúc đóng cửa
            $now = new DateTimeImmutable();
            foreach ($courts as $court) {
                $count = 0;
                for ($hour = OPEN_HOUR; $hour < CLOSE_HOUR; $hour++) {
                    $slotStart = new DateTimeImmutable(sprintf('%s %02d:00:00', date('Y-m-d'), $hour));
                    if ($slotStart > $now && empty($booked[$court['id']][$hour])) {
                        $count++;
                    }
                }
                $freeSlots[$court['id']] = $count;
            }

            if ($courts) {
                $minPrice = (float) min(array_column($courts, 'price_per_hour'));
            }
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            $pageError = 'Không thể tải dữ liệu. Hãy kiểm tra cấu hình kết nối và chạy tệp database.sql.';
        }

        render('home', [
            'courts' => $courts,
            'freeSlots' => $freeSlots,
            'minPrice' => $minPrice,
            'pageError' => $pageError,
        ]);
    }
}