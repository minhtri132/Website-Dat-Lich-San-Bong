<?php
class BookingController
{
    private ?BookingModel $model = null;
    private ?CourtModel $courtModel = null;

    // Trang chủ: bảng giờ trống theo ngày
    public function index(): void
    {
        $date = parseDate($_GET['date'] ?? null) ?? date('Y-m-d');
        $courts = [];
        $bookings = [];
        $pageError = null;

        try {
            $courts = $this->courts()->getActive();
            $bookings = $this->model()->getBookingsByDate($date);
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            $pageError = 'Không thể tải dữ liệu. Hãy kiểm tra cấu hình kết nối và chạy tệp database.sql.';
        }

        $user = currentUser();
        $now = new DateTimeImmutable();
        $cells = [];

        // Mặc định: ô nào đã qua giờ thì "past", còn lại "free"
        for ($hour = OPEN_HOUR; $hour < CLOSE_HOUR; $hour++) {
            $slotStart = new DateTimeImmutable(sprintf('%s %02d:00:00', $date, $hour));
            foreach ($courts as $court) {
                $cells[$hour][$court['id']] = [
                    'state' => $slotStart <= $now ? 'past' : 'free',
                    'label' => '',
                ];
            }
        }

        // Đánh dấu các ô đã có người đặt
        foreach ($bookings as $booking) {
            $startHour = (int) (new DateTimeImmutable($booking['start_at']))->format('G');
            $endHour = (int) (new DateTimeImmutable($booking['end_at']))->format('G');
            $mine = $user && (int) $booking['user_id'] === $user['id'];

            for ($hour = $startHour; $hour < $endHour; $hour++) {
                if (!isset($cells[$hour][$booking['court_id']])) {
                    continue;
                }
                $cells[$hour][$booking['court_id']] = [
                    'state' => $mine ? 'mine' : 'booked',
                    'label' => isAdmin() ? $booking['customer_name'] : ($mine ? 'Của bạn' : 'Đã đặt'),
                ];
            }
        }

        $day = new DateTimeImmutable($date);
        render('booking/index', [
            'date' => $date,
            'courts' => $courts,
            'cells' => $cells,
            'pageError' => $pageError,
            'prevDate' => $day->modify('-1 day')->format('Y-m-d'),
            'nextDate' => $day->modify('+1 day')->format('Y-m-d'),
        ]);
    }

    // Trang xác nhận đặt sân (khách bấm vào ô trống)
    public function form(): void
    {
        requireLogin();

        $courtId = filter_var($_GET['court_id'] ?? null, FILTER_VALIDATE_INT);
        $date = parseDate($_GET['date'] ?? null);
        $hour = filter_var($_GET['hour'] ?? null, FILTER_VALIDATE_INT);

        if (!$courtId || !$date || $hour === false || $hour < OPEN_HOUR || $hour >= CLOSE_HOUR) {
            setFlash('error', 'Thông tin khung giờ không hợp lệ.');
            redirect('/');
        }

        $startAt = new DateTimeImmutable(sprintf('%s %02d:00:00', $date, $hour));

        try {
            $court = $this->courts()->find($courtId);
            if (!$court || !$court['is_active']) {
                throw new DomainException('Sân không tồn tại hoặc đang ngừng hoạt động.');
            }
            if ($startAt <= new DateTimeImmutable()) {
                throw new DomainException('Khung giờ này đã qua, hãy chọn giờ khác.');
            }

            // Tính số giờ tối đa có thể đặt liên tiếp (không đè lên lịch khác)
            $maxHours = 0;
            $limit = min(MAX_HOURS, CLOSE_HOUR - $hour);
            for ($n = 1; $n <= $limit; $n++) {
                if ($this->model()->hasOverlap($courtId, $startAt, $startAt->modify('+' . $n . ' hour'))) {
                    break;
                }
                $maxHours = $n;
            }
            if ($maxHours === 0) {
                throw new DomainException('Khung giờ này đã có người đặt.');
            }
        } catch (DomainException $exception) {
            setFlash('error', $exception->getMessage());
            redirect('/', ['date' => $date]);
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            setFlash('error', 'Không thể tải dữ liệu. Vui lòng thử lại.');
            redirect('/', ['date' => $date]);
        }

        render('booking/form', [
            'court' => $court,
            'date' => $date,
            'hour' => $hour,
            'maxHours' => $maxHours,
            'user' => currentUser(),
        ]);
    }

    public function create(): void
    {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/');
        }

        $date = parseDate($_POST['date'] ?? null) ?? date('Y-m-d');
        $success = false;

        try {
            assertCsrf();

            $courtId = filter_var($_POST['court_id'] ?? null, FILTER_VALIDATE_INT);
            $hour = filter_var($_POST['hour'] ?? null, FILTER_VALIDATE_INT);
            $hours = filter_var($_POST['hours'] ?? null, FILTER_VALIDATE_INT);
            $name = trim((string) ($_POST['customer_name'] ?? ''));
            $phone = preg_replace('/[\s().-]/', '', trim((string) ($_POST['phone'] ?? '')));

            if (!$courtId || $hour === false || $hours === false) {
                throw new DomainException('Thông tin đặt sân không hợp lệ.');
            }
            if (!preg_match('/^.{2,100}$/u', $name)) {
                throw new DomainException('Tên khách hàng phải từ 2 đến 100 ký tự.');
            }
            if (!preg_match('/^\+?\d{9,15}$/D', $phone)) {
                throw new DomainException('Số điện thoại phải có từ 9 đến 15 chữ số.');
            }
            if ($hour < OPEN_HOUR || $hours < 1 || $hours > MAX_HOURS || $hour + $hours > CLOSE_HOUR) {
                throw new DomainException('Giờ đặt phải trong khung ' . sprintf('%02d:00–%02d:00', OPEN_HOUR, CLOSE_HOUR) . ' và tối đa ' . MAX_HOURS . ' giờ.');
            }

            $startAt = new DateTimeImmutable(sprintf('%s %02d:00:00', $date, $hour));
            if ($startAt <= new DateTimeImmutable()) {
                throw new DomainException('Không thể đặt sân vào thời điểm đã qua.');
            }

            $this->model()->createBooking($courtId, currentUser()['id'], $name, $phone, $startAt, $hours);
            setFlash('success', 'Đặt sân thành công!');
            $success = true;
        } catch (DomainException $exception) {
            setFlash('error', $exception->getMessage());
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            setFlash('error', 'Không thể lưu lịch đặt sân. Vui lòng thử lại.');
        }

        $success ? redirect('my-bookings') : redirect('/', ['date' => $date]);
    }

    public function cancel(): void
    {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/');
        }

        $return = ($_POST['return'] ?? '') === 'admin-bookings' && isAdmin() ? 'admin-bookings' : 'my-bookings';
        $params = [];
        if ($return === 'admin-bookings') {
            // Giữ lại bộ lọc đang dùng
            if (array_key_exists('filter_date', $_POST)) {
                $params['date'] = parseDate($_POST['filter_date']) ?? '';
            }
            if (in_array($_POST['filter_status'] ?? '', ['confirmed', 'cancelled'], true)) {
                $params['status'] = $_POST['filter_status'];
            }
        }

        try {
            assertCsrf();
            $bookingId = filter_var($_POST['booking_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$bookingId) {
                throw new DomainException('Lịch đặt sân không hợp lệ.');
            }

            // Admin hủy được mọi lịch, khách chỉ hủy được lịch của mình
            $ownerId = isAdmin() ? null : currentUser()['id'];
            if (!$this->model()->cancelBooking($bookingId, $ownerId)) {
                throw new DomainException('Không thể hủy: lịch không tồn tại, đã hủy hoặc đã bắt đầu.');
            }
            setFlash('success', 'Đã hủy lịch đặt sân.');
        } catch (DomainException $exception) {
            setFlash('error', $exception->getMessage());
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            setFlash('error', 'Không thể hủy lịch lúc này. Vui lòng thử lại.');
        }

        redirect($return, $params);
    }

    public function myBookings(): void
    {
        requireLogin();

        $bookings = [];
        $pageError = null;
        try {
            $bookings = $this->model()->getByUser(currentUser()['id']);
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            $pageError = 'Không thể tải lịch đặt của bạn.';
        }

        render('booking/my', ['bookings' => $bookings, 'pageError' => $pageError]);
    }

    // Admin: xem toàn bộ lịch, lọc theo ngày và trạng thái
    public function adminList(): void
    {
        requireAdmin();

        // Không có tham số date: xem hôm nay. Có date nhưng để trống: xem tất cả các ngày.
        $date = array_key_exists('date', $_GET) ? parseDate($_GET['date']) : date('Y-m-d');
        $status = in_array($_GET['status'] ?? '', ['confirmed', 'cancelled'], true) ? $_GET['status'] : null;

        $bookings = [];
        $pageError = null;
        try {
            $bookings = $this->model()->getForAdmin($date, $status);
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            $pageError = 'Không thể tải dữ liệu lịch đặt.';
        }

        $count = 0;
        $revenue = 0.0;
        foreach ($bookings as $booking) {
            if ($booking['status'] === 'confirmed') {
                $count++;
                $revenue += (float) $booking['total_price'];
            }
        }

        render('admin/bookings', [
            'bookings' => $bookings,
            'date' => $date,
            'status' => $status,
            'count' => $count,
            'revenue' => $revenue,
            'pageError' => $pageError,
        ]);
    }

    private function model(): BookingModel
    {
        if ($this->model === null) {
            $this->model = new BookingModel();
        }
        return $this->model;
    }

    private function courts(): CourtModel
    {
        if ($this->courtModel === null) {
            $this->courtModel = new CourtModel();
        }
        return $this->courtModel;
    }
}