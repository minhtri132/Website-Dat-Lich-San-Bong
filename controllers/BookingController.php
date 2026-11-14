<?php
class BookingController
{
    private ?BookingModel $model = null;
    private ?CourtModel $courtModel = null;

    // Trang đặt sân: bảng giờ trống theo ngày
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

        // Đánh dấu các ô đang giữ sân (đã xác nhận hoặc đang chờ đặt cọc)
        foreach ($bookings as $booking) {
            $startHour = (int) (new DateTimeImmutable($booking['start_at']))->format('G');
            $endHour = (int) (new DateTimeImmutable($booking['end_at']))->format('G');
            $mine = $user && (int) $booking['user_id'] === $user['id'];
            $holding = $booking['status'] === 'pending';

            if (isAdmin()) {
                $label = $booking['customer_name'] . ($holding ? ' (chờ cọc)' : '');
            } elseif ($mine) {
                $label = $holding ? 'Chờ cọc' : 'Của bạn';
            } else {
                $label = $holding ? 'Đang giữ chỗ' : 'Đã đặt';
            }

            for ($hour = $startHour; $hour < $endHour; $hour++) {
                if (!isset($cells[$hour][$booking['court_id']])) {
                    continue;
                }
                $cells[$hour][$booking['court_id']] = [
                    'state' => $mine ? 'mine' : 'booked',
                    'label' => $label,
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
            redirect('booking');
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
            redirect('booking', ['date' => $date]);
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            setFlash('error', 'Không thể tải dữ liệu. Vui lòng thử lại.');
            redirect('booking', ['date' => $date]);
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
            redirect('booking');
        }

        $date = parseDate($_POST['date'] ?? null) ?? date('Y-m-d');
        $bookingId = null;

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

            $bookingId = $this->model()->createBooking(
                $courtId,
                currentUser()['id'],
                $name,
                $phone,
                $startAt,
                $hours,
                !isAdmin()
            );

            $booking = $this->model()->find($bookingId);
            if ($booking && $booking['status'] === 'pending') {
                setFlash('success', 'Đã giữ chỗ! Hãy đặt cọc trong ' . HOLD_MINUTES . ' phút để hoàn tất đặt sân.');
            } else {
                setFlash('success', 'Đặt sân thành công!');
            }
        } catch (DomainException $exception) {
            setFlash('error', $exception->getMessage());
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            setFlash('error', 'Không thể lưu lịch đặt sân. Vui lòng thử lại.');
        }

        $bookingId ? redirect('booking-detail', ['id' => $bookingId]) : redirect('booking', ['date' => $date]);
    }

    public function cancel(): void
    {
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('booking');
        }

        $bookingId = filter_var($_POST['booking_id'] ?? null, FILTER_VALIDATE_INT);
        $return = $_POST['return'] ?? '';
        if ($return === 'admin-bookings' && !isAdmin()) {
            $return = 'my-bookings';
        }
        if (!in_array($return, ['my-bookings', 'admin-bookings', 'booking-detail'], true)) {
            $return = 'my-bookings';
        }

        $params = [];
        if ($return === 'admin-bookings') {
            // Giữ lại bộ lọc đang dùng
            if (array_key_exists('filter_date', $_POST)) {
                $params['date'] = parseDate($_POST['filter_date']) ?? '';
            }
            if (in_array($_POST['filter_status'] ?? '', ['pending', 'confirmed', 'cancelled'], true)) {
                $params['status'] = $_POST['filter_status'];
            }
        } elseif ($return === 'booking-detail' && $bookingId) {
            $params['id'] = $bookingId;
        } elseif ($return === 'booking-detail') {
            $return = 'my-bookings';
        }

        try {
            assertCsrf();
            if (!$bookingId) {
                throw new DomainException('Lịch đặt sân không hợp lệ.');
            }

            // Admin hủy được mọi lịch (hoàn toàn bộ tiền), khách chỉ hủy lịch của mình theo chính sách
            $ownerId = isAdmin() ? null : currentUser()['id'];
            $result = $this->model()->cancelBooking($bookingId, $ownerId);

            $message = 'Đã hủy lịch đặt sân.';
            if ($result['refund'] > 0) {
                $message .= ' Số tiền ' . formatMoney($result['refund']) . ' sẽ được hoàn lại.';
            }
            if ($result['forfeit'] > 0) {
                $message .= ' Tiền cọc ' . formatMoney($result['forfeit']) . ' không được hoàn do hủy sát giờ (trước giờ đá dưới ' . CANCEL_FREE_HOURS . ' giờ).';
            }
            setFlash('success', $message);
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
        $status = in_array($_GET['status'] ?? '', ['pending', 'confirmed', 'cancelled'], true) ? $_GET['status'] : null;

        $bookings = [];
        $pageError = null;
        try {
            $bookings = $this->model()->getForAdmin($date, $status);
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            $pageError = 'Không thể tải dữ liệu lịch đặt.';
        }

        // count: lịch đang giữ sân; total: giá trị các lịch đó;
        // collected: tiền đã thu thực tế (đã trừ hoàn tiền); outstanding: tiền còn phải thu
        $count = 0;
        $total = 0.0;
        $collected = 0.0;
        $outstanding = 0.0;
        foreach ($bookings as $booking) {
            $collected += (float) $booking['paid_amount'];
            if ($booking['status'] !== 'cancelled') {
                $count++;
                $total += (float) $booking['total_price'];
                $outstanding += remainingAmount($booking);
            }
        }

        render('admin/bookings', [
            'bookings' => $bookings,
            'date' => $date,
            'status' => $status,
            'count' => $count,
            'total' => $total,
            'collected' => $collected,
            'outstanding' => $outstanding,
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