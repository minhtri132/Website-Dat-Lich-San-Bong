<?php
// Trang quản trị: tổng quan và quản lý khách hàng (chỉ admin)
class AdminController
{
        public function dashboard(): void
    {
        requireAdmin();

        $stats = [
            'today_count' => 0, 'today_collected' => 0,
            'month_count' => 0, 'month_collected' => 0,
            'pending_count' => 0, 'pending_reported' => 0,
            'refund_count' => 0, 'refund_amount' => 0,
            'outstanding' => 0,
            'courts_total' => 0, 'courts_active' => 0,
            'customers' => 0,
        ];
        $chart = [];
        $upcoming = [];
        $pageError = null;

        try {
            $bookings = new BookingModel();
            $today = new DateTimeImmutable('today');
            $tomorrow = $today->modify('+1 day');
            $monthStart = $today->modify('first day of this month');
            $nextMonth = $monthStart->modify('+1 month');

            $stats['today_count'] = (int) $bookings->summaryBetween($today->format('Y-m-d'), $tomorrow->format('Y-m-d'))['cnt'];
            $stats['month_count'] = (int) $bookings->summaryBetween($monthStart->format('Y-m-d'), $nextMonth->format('Y-m-d'))['cnt'];
            $stats['today_collected'] = $bookings->collectedBetween($today->format('Y-m-d'), $tomorrow->format('Y-m-d'));
            $stats['month_collected'] = $bookings->collectedBetween($monthStart->format('Y-m-d'), $nextMonth->format('Y-m-d'));

            $pending = $bookings->pendingDeposits();
            $stats['pending_count'] = (int) $pending['cnt'];
            $stats['pending_reported'] = (int) $pending['reported'];

            $refunds = $bookings->refundsDue();
            $stats['refund_count'] = (int) $refunds['cnt'];
            $stats['refund_amount'] = (float) $refunds['amount'];

            $stats['outstanding'] = $bookings->outstandingUpcoming();

            $courts = (new CourtModel())->getAll();
            $stats['courts_total'] = count($courts);
            $stats['courts_active'] = count(array_filter($courts, fn ($court) => (int) $court['is_active'] === 1));
            $stats['customers'] = (new UserModel())->countCustomers();

            // Giá trị các lịch theo ngày: 3 ngày trước, hôm nay và 3 ngày tới
            $from = $today->modify('-3 days');
            $byDay = $bookings->revenueByDay($from->format('Y-m-d'), $today->modify('+4 days')->format('Y-m-d'));
            $max = max(1, ...array_values($byDay ?: [0]));
            for ($i = 0; $i < 7; $i++) {
                $day = $from->modify('+' . $i . ' day');
                $key = $day->format('Y-m-d');
                $value = $byDay[$key] ?? 0;
                $chart[] = [
                    'label' => $day->format('d/m'),
                    'value' => $value,
                    'percent' => (int) round($value / $max * 100),
                    'is_today' => $i === 3,
                ];
            }

            $upcoming = $bookings->getUpcoming(8);
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            $pageError = 'Không thể tải dữ liệu thống kê.';
        }

        render('admin/dashboard', [
            'stats' => $stats,
            'chart' => $chart,
            'upcoming' => $upcoming,
            'pageError' => $pageError,
        ]);
    }

    public function users(): void
    {
        requireAdmin();

        $keyword = trim((string) ($_GET['q'] ?? ''));
        $users = [];
        $pageError = null;

        try {
            $users = (new UserModel())->searchCustomers($keyword);
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            $pageError = 'Không thể tải danh sách khách hàng.';
        }

        render('admin/users', ['users' => $users, 'keyword' => $keyword, 'pageError' => $pageError]);
    }
}