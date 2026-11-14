<?php
// Chi tiết lịch đặt và các thao tác thanh toán
class PaymentController
{
    private ?BookingModel $model = null;

    // Trang chi tiết: khách xem lịch của mình, admin xem mọi lịch
    public function detail(): void
    {
        requireLogin();

        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        $fallback = isAdmin() ? 'admin-bookings' : 'my-bookings';
        $booking = null;
        $payments = [];

        try {
            if ($id) {
                $booking = $this->model()->find($id);
            }
            if ($booking && !$this->canView($booking)) {
                $booking = null;
            }
            if ($booking) {
                $payments = $this->model()->getPayments($id);
            }
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            setFlash('error', 'Không thể tải thông tin lịch đặt.');
            redirect($fallback);
        }

        if (!$booking) {
            setFlash('error', 'Không tìm thấy lịch đặt.');
            redirect($fallback);
        }

        render('booking/detail', ['booking' => $booking, 'payments' => $payments]);
    }

    // Khách báo đã chuyển khoản cọc
    public function report(): void
    {
        requireLogin();
        $this->requirePost('my-bookings');

        $id = filter_var($_POST['booking_id'] ?? null, FILTER_VALIDATE_INT);

        try {
            assertCsrf();
            $booking = $id ? $this->model()->find($id) : null;
            if (!$booking || (int) $booking['user_id'] !== currentUser()['id'] || $booking['status'] !== 'pending') {
                throw new DomainException('Không thể gửi thông báo cho lịch này.');
            }
            $this->model()->reportTransfer($id, currentUser()['id']);
            setFlash('success', 'Đã gửi thông báo. Nhân viên sẽ kiểm tra và xác nhận cọc cho bạn sớm.');
        } catch (DomainException $exception) {
            setFlash('error', $exception->getMessage());
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            setFlash('error', 'Không thể gửi thông báo lúc này. Vui lòng thử lại.');
        }

        $id ? redirect('booking-detail', ['id' => $id]) : redirect('my-bookings');
    }

    // Admin ghi nhận một khoản thu
    public function add(): void
    {
        requireAdmin();
        $this->requirePost('admin-bookings');

        $id = filter_var($_POST['booking_id'] ?? null, FILTER_VALIDATE_INT);

        try {
            assertCsrf();
            $amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_INT);
            $method = $_POST['method'] ?? '';
            $note = trim((string) ($_POST['note'] ?? ''));

            if (!$id || $amount === false || $amount <= 0) {
                throw new DomainException('Số tiền không hợp lệ.');
            }
            if (!in_array($method, ['cash', 'transfer'], true)) {
                throw new DomainException('Hình thức thanh toán không hợp lệ.');
            }
            if (!preg_match('/^.{0,255}$/us', $note)) {
                throw new DomainException('Ghi chú tối đa 255 ký tự.');
            }

            $this->model()->recordPayment($id, (float) $amount, $method, $note === '' ? null : $note, currentUser()['id']);
            setFlash('success', 'Đã ghi nhận khoản thu ' . formatMoney($amount) . '.');
        } catch (DomainException $exception) {
            setFlash('error', $exception->getMessage());
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            setFlash('error', 'Không thể ghi nhận khoản thu. Vui lòng thử lại.');
        }

        $id ? redirect('booking-detail', ['id' => $id]) : redirect('admin-bookings');
    }

    // Admin xác nhận đã hoàn tiền cho khách
    public function refund(): void
    {
        requireAdmin();
        $this->requirePost('admin-bookings');

        $id = filter_var($_POST['booking_id'] ?? null, FILTER_VALIDATE_INT);

        try {
            assertCsrf();
            $method = $_POST['method'] ?? '';
            if (!$id || !in_array($method, ['cash', 'transfer'], true)) {
                throw new DomainException('Thông tin hoàn tiền không hợp lệ.');
            }
            $this->model()->completeRefund($id, $method, currentUser()['id']);
            setFlash('success', 'Đã ghi nhận hoàn tiền cho khách.');
        } catch (DomainException $exception) {
            setFlash('error', $exception->getMessage());
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            setFlash('error', 'Không thể ghi nhận hoàn tiền. Vui lòng thử lại.');
        }

        $id ? redirect('booking-detail', ['id' => $id]) : redirect('admin-bookings');
    }

    private function canView(array $booking): bool
    {
        return isAdmin() || (int) $booking['user_id'] === currentUser()['id'];
    }

    private function requirePost(string $fallback): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect($fallback);
        }
    }

    private function model(): BookingModel
    {
        if ($this->model === null) {
            $this->model = new BookingModel();
        }
        return $this->model;
    }
}