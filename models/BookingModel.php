<?php
class BookingModel
{
    private PDO $conn;

    // Số tiền đã thu của một lịch (đã trừ các khoản hoàn tiền), dùng với bảng bookings có bí danh b
    private const PAID = '(SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.booking_id = b.id)';

    public function __construct()
    {
        $this->conn = connectDB();
        $this->expireStaleHolds();
    }

    // Tự hủy các lịch giữ chỗ đã quá hạn mà chưa đặt cọc đủ, nhả sân cho người khác
    public function expireStaleHolds(): void
    {
        $paid = '(SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.booking_id = bookings.id)';
        $statement = $this->conn->prepare(
            "UPDATE bookings
             SET status = 'cancelled',
                 cancelled_at = :cancelled_at,
                 cancel_reason = 'Quá hạn đặt cọc',
                 hold_until = NULL,
                 refund_amount = $paid,
                 refund_status = IF($paid > 0, 'pending', 'none')
             WHERE status = 'pending' AND hold_until IS NOT NULL AND hold_until < :now"
        );
        $now = date('Y-m-d H:i:s');
        $statement->execute(['cancelled_at' => $now, 'now' => $now]);
    }

    // Các lịch còn giữ sân (đang chờ cọc hoặc đã xác nhận) trong một ngày, dùng để vẽ bảng giờ trống
    public function getBookingsByDate(string $date): array
    {
        $statement = $this->conn->prepare(
            "SELECT id, court_id, user_id, customer_name, status, start_at, end_at
             FROM bookings
             WHERE status IN ('pending', 'confirmed')
               AND start_at >= :day_start AND start_at < :next_day"
        );
        $statement->execute([
            'day_start' => $date . ' 00:00:00',
            'next_day' => (new DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d 00:00:00'),
        ]);

        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->conn->prepare(
            'SELECT b.*, c.name AS court_name, ' . self::PAID . ' AS paid_amount
             FROM bookings b
             INNER JOIN courts c ON c.id = b.court_id
             WHERE b.id = :id'
        );
        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function getPayments(int $bookingId): array
    {
        $statement = $this->conn->prepare(
            'SELECT p.*, u.full_name AS created_by_name
             FROM payments p
             LEFT JOIN users u ON u.id = p.created_by
             WHERE p.booking_id = :id
             ORDER BY p.created_at ASC, p.id ASC'
        );
        $statement->execute(['id' => $bookingId]);

        return $statement->fetchAll();
    }

    // Lịch sử đặt sân của một khách
    public function getByUser(int $userId): array
    {
        $statement = $this->conn->prepare(
            'SELECT b.*, c.name AS court_name, ' . self::PAID . ' AS paid_amount
             FROM bookings b
             INNER JOIN courts c ON c.id = b.court_id
             WHERE b.user_id = :user_id
             ORDER BY b.start_at DESC'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }

    // Danh sách cho admin, có lọc theo ngày và trạng thái (null = tất cả)
    public function getForAdmin(?string $date, ?string $status): array
    {
        $sql = 'SELECT b.*, c.name AS court_name, ' . self::PAID . ' AS paid_amount
                FROM bookings b
                INNER JOIN courts c ON c.id = b.court_id
                WHERE 1 = 1';
        $params = [];

        if ($date !== null) {
            $sql .= ' AND b.start_at >= :day_start AND b.start_at < :next_day';
            $params['day_start'] = $date . ' 00:00:00';
            $params['next_day'] = (new DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d 00:00:00');
        }
        if ($status !== null) {
            $sql .= ' AND b.status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY b.start_at DESC, c.name';

        $statement = $this->conn->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    // Kiểm tra khoảng thời gian có bị trùng với lịch đang giữ sân khác không
    public function hasOverlap(int $courtId, DateTimeImmutable $startAt, DateTimeImmutable $endAt): bool
    {
        $statement = $this->conn->prepare(
            "SELECT 1 FROM bookings
             WHERE court_id = :court_id
               AND status IN ('pending', 'confirmed')
               AND start_at < :end_at
               AND end_at > :start_at
             LIMIT 1"
        );
        $statement->execute([
            'court_id' => $courtId,
            'end_at' => $endAt->format('Y-m-d H:i:s'),
            'start_at' => $startAt->format('Y-m-d H:i:s'),
        ]);

        return (bool) $statement->fetchColumn();
    }

    // Tạo lịch đặt, trả về id. Có cọc thì lịch ở trạng thái "pending" và bị giữ chỗ trong HOLD_MINUTES phút
    public function createBooking(
        int $courtId,
        int $userId,
        string $customerName,
        string $phone,
        DateTimeImmutable $startAt,
        int $hours,
        bool $limitPending = true
    ): int {
        $endAt = $startAt->modify('+' . $hours . ' hour');
        $this->conn->beginTransaction();

        try {
            // Khóa dòng của sân để 2 người không đặt trùng cùng lúc
            $court = $this->conn->prepare(
                'SELECT price_per_hour FROM courts WHERE id = :id AND is_active = 1 FOR UPDATE'
            );
            $court->execute(['id' => $courtId]);
            $price = $court->fetchColumn();

            if ($price === false) {
                throw new DomainException('Sân đã chọn không tồn tại hoặc đang ngừng hoạt động.');
            }

            if ($this->hasOverlap($courtId, $startAt, $endAt)) {
                throw new DomainException('Khung giờ này đã có người đặt sân.');
            }

            if ($limitPending) {
                $count = $this->conn->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = :user_id AND status = 'pending'");
                $count->execute(['user_id' => $userId]);
                $pending = (int) $count->fetchColumn();
                if ($pending >= MAX_PENDING_BOOKINGS) {
                    throw new DomainException(
                        'Bạn đang có ' . $pending . ' lịch chờ đặt cọc. Hãy đặt cọc hoặc hủy bớt trước khi đặt thêm.'
                    );
                }
            }

            $total = (float) $price * $hours;
            $deposit = depositFor($total);
            $status = $deposit > 0 ? 'pending' : 'confirmed';
            $holdUntil = $deposit > 0
                ? (new DateTimeImmutable())->modify('+' . HOLD_MINUTES . ' minutes')->format('Y-m-d H:i:s')
                : null;

            $insert = $this->conn->prepare(
                'INSERT INTO bookings
                    (court_id, user_id, customer_name, phone, start_at, end_at, price_per_hour,
                     total_price, deposit_amount, status, hold_until)
                 VALUES
                    (:court_id, :user_id, :customer_name, :phone, :start_at, :end_at, :price,
                     :total, :deposit, :status, :hold_until)'
            );
            $insert->execute([
                'court_id' => $courtId,
                'user_id' => $userId,
                'customer_name' => $customerName,
                'phone' => $phone,
                'start_at' => $startAt->format('Y-m-d H:i:s'),
                'end_at' => $endAt->format('Y-m-d H:i:s'),
                'price' => $price,
                'total' => $total,
                'deposit' => $deposit,
                'status' => $status,
                'hold_until' => $holdUntil,
            ]);
            $bookingId = (int) $this->conn->lastInsertId();

            $this->conn->commit();

            return $bookingId;
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $exception;
        }
    }

    // Khách báo đã chuyển khoản để admin kiểm tra
    public function reportTransfer(int $bookingId, int $userId): void
    {
        $statement = $this->conn->prepare(
            "UPDATE bookings SET transfer_reported = 1
             WHERE id = :id AND user_id = :user_id AND status = 'pending'"
        );
        $statement->execute(['id' => $bookingId, 'user_id' => $userId]);
    }

    // Admin ghi nhận một khoản thu. Đủ tiền cọc thì lịch tự chuyển sang "confirmed"
    public function recordPayment(int $bookingId, float $amount, string $method, ?string $note, int $adminId): void
    {
        $this->conn->beginTransaction();

        try {
            $booking = $this->lockBooking($bookingId);

            if ($booking['status'] === 'cancelled') {
                throw new DomainException('Lịch đã hủy, không thể thu thêm tiền.');
            }
            if ($amount <= 0) {
                throw new DomainException('Số tiền phải lớn hơn 0.');
            }

            $paid = (float) $booking['paid_amount'];
            $total = (float) $booking['total_price'];
            if ($paid + $amount > $total) {
                throw new DomainException('Số tiền vượt quá phần còn lại (' . formatMoney($total - $paid) . ').');
            }

            $insert = $this->conn->prepare(
                "INSERT INTO payments (booking_id, amount, method, type, note, created_by, created_at)
                 VALUES (:booking_id, :amount, :method, 'payment', :note, :created_by, :created_at)"
            );
            $insert->execute([
                'booking_id' => $bookingId,
                'amount' => $amount,
                'method' => $method,
                'note' => $note,
                'created_by' => $adminId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // Đủ tiền cọc: xác nhận lịch và bỏ hạn giữ chỗ
            if ($booking['status'] === 'pending' && $paid + $amount >= (float) $booking['deposit_amount']) {
                $confirm = $this->conn->prepare(
                    "UPDATE bookings SET status = 'confirmed', hold_until = NULL, transfer_reported = 0 WHERE id = :id"
                );
                $confirm->execute(['id' => $bookingId]);
            }

            $this->conn->commit();
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $exception;
        }
    }

    // Hủy lịch và tính số tiền được hoàn theo chính sách. $userId = null nghĩa là admin hủy (hoàn toàn bộ)
    // Trả về ['refund' => số tiền cần hoàn, 'forfeit' => số tiền cọc bị giữ lại]
    public function cancelBooking(int $bookingId, ?int $userId): array
    {
        $this->conn->beginTransaction();

        try {
            $booking = $this->lockBooking($bookingId);

            if ($userId !== null && (int) $booking['user_id'] !== $userId) {
                throw new DomainException('Không tìm thấy lịch đặt.');
            }
            if ($booking['status'] === 'cancelled') {
                throw new DomainException('Lịch này đã được hủy.');
            }

            $startAt = new DateTimeImmutable($booking['start_at']);
            $now = new DateTimeImmutable();
            if ($startAt <= $now) {
                throw new DomainException('Lịch đã bắt đầu nên không thể hủy.');
            }

            $paid = max(0.0, (float) $booking['paid_amount']);
            if ($userId === null) {
                $refund = $paid;
                $reason = 'Quản trị viên hủy lịch';
            } elseif ($booking['status'] === 'pending') {
                $refund = $paid;
                $reason = 'Khách hủy khi chưa đặt cọc đủ';
            } else {
                $hoursLeft = ($startAt->getTimestamp() - $now->getTimestamp()) / 3600;
                if ($hoursLeft >= CANCEL_FREE_HOURS) {
                    $refund = $paid;
                    $reason = 'Khách hủy sớm, được hoàn tiền';
                } else {
                    // Hủy sát giờ: giữ lại phần tiền cọc, chỉ hoàn phần đã trả vượt quá cọc
                    $refund = max(0.0, $paid - (float) $booking['deposit_amount']);
                    $reason = 'Khách hủy sát giờ, không hoàn tiền cọc';
                }
            }

            $update = $this->conn->prepare(
                "UPDATE bookings
                 SET status = 'cancelled', cancelled_at = :cancelled_at, cancel_reason = :reason,
                     hold_until = NULL, refund_amount = :refund, refund_status = :refund_status
                 WHERE id = :id"
            );
            $update->execute([
                'cancelled_at' => $now->format('Y-m-d H:i:s'),
                'reason' => $reason,
                'refund' => $refund,
                'refund_status' => $refund > 0 ? 'pending' : 'none',
                'id' => $bookingId,
            ]);

            $this->conn->commit();

            return ['refund' => $refund, 'forfeit' => $paid - $refund];
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $exception;
        }
    }

    // Admin xác nhận đã hoàn tiền cho khách: ghi một khoản âm vào lịch sử thanh toán
    public function completeRefund(int $bookingId, string $method, int $adminId): void
    {
        $this->conn->beginTransaction();

        try {
            $booking = $this->lockBooking($bookingId);

            if ($booking['refund_status'] !== 'pending' || (float) $booking['refund_amount'] <= 0) {
                throw new DomainException('Lịch này không có khoản nào cần hoàn.');
            }

            $insert = $this->conn->prepare(
                "INSERT INTO payments (booking_id, amount, method, type, note, created_by, created_at)
                 VALUES (:booking_id, :amount, :method, 'refund', :note, :created_by, :created_at)"
            );
            $insert->execute([
                'booking_id' => $bookingId,
                'amount' => -1 * (float) $booking['refund_amount'],
                'method' => $method,
                'note' => 'Hoàn tiền khi hủy lịch #' . $bookingId,
                'created_by' => $adminId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $done = $this->conn->prepare("UPDATE bookings SET refund_status = 'done' WHERE id = :id");
            $done->execute(['id' => $bookingId]);

            $this->conn->commit();
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $exception;
        }
    }

    // Khóa dòng của lịch trong transaction để các thao tác tiền bạc không chạy chồng nhau
    private function lockBooking(int $bookingId): array
    {
        $statement = $this->conn->prepare(
            'SELECT b.*, ' . self::PAID . ' AS paid_amount FROM bookings b WHERE b.id = :id FOR UPDATE'
        );
        $statement->execute(['id' => $bookingId]);
        $booking = $statement->fetch();

        if (!$booking) {
            throw new DomainException('Không tìm thấy lịch đặt.');
        }

        return $booking;
    }

    // ===== Thống kê cho trang tổng quan =====

    // Số lịch và tổng giá trị các lịch đang giữ sân trong khoảng [from, to)
    public function summaryBetween(string $from, string $to): array
    {
        $statement = $this->conn->prepare(
            "SELECT COUNT(*) AS cnt, COALESCE(SUM(total_price), 0) AS total
             FROM bookings
             WHERE status IN ('pending', 'confirmed') AND start_at >= :from_at AND start_at < :to_at"
        );
        $statement->execute(['from_at' => $from . ' 00:00:00', 'to_at' => $to . ' 00:00:00']);

        return $statement->fetch();
    }

    // Tiền thực thu (đã trừ hoàn tiền) trong khoảng [from, to)
    public function collectedBetween(string $from, string $to): float
    {
        $statement = $this->conn->prepare(
            'SELECT COALESCE(SUM(amount), 0) FROM payments WHERE created_at >= :from_at AND created_at < :to_at'
        );
        $statement->execute(['from_at' => $from . ' 00:00:00', 'to_at' => $to . ' 00:00:00']);

        return (float) $statement->fetchColumn();
    }

    // Số lịch đang chờ đặt cọc và số lịch khách đã báo chuyển khoản
    public function pendingDeposits(): array
    {
        return $this->conn->query(
            "SELECT COUNT(*) AS cnt, COALESCE(SUM(transfer_reported), 0) AS reported
             FROM bookings WHERE status = 'pending'"
        )->fetch();
    }

    // Số lịch và tổng tiền cần hoàn cho khách
    public function refundsDue(): array
    {
        return $this->conn->query(
            "SELECT COUNT(*) AS cnt, COALESCE(SUM(refund_amount), 0) AS amount
             FROM bookings WHERE refund_status = 'pending'"
        )->fetch();
    }

    // Tiền còn phải thu của các lịch đã xác nhận và chưa diễn ra
    public function outstandingUpcoming(): float
    {
        $statement = $this->conn->prepare(
            "SELECT COALESCE(SUM(b.total_price - " . self::PAID . "), 0)
             FROM bookings b
             WHERE b.status = 'confirmed' AND b.start_at > :now"
        );
        $statement->execute(['now' => date('Y-m-d H:i:s')]);

        return (float) $statement->fetchColumn();
    }

    // Giá trị các lịch theo từng ngày trong khoảng [from, to) -> ['2026-11-08' => 600000, ...]
    public function revenueByDay(string $from, string $to): array
    {
        $statement = $this->conn->prepare(
            "SELECT DATE(start_at) AS day, SUM(total_price) AS revenue
             FROM bookings
             WHERE status IN ('pending', 'confirmed') AND start_at >= :from_at AND start_at < :to_at
             GROUP BY DATE(start_at)"
        );
        $statement->execute(['from_at' => $from . ' 00:00:00', 'to_at' => $to . ' 00:00:00']);

        $result = [];
        foreach ($statement->fetchAll() as $row) {
            $result[$row['day']] = (float) $row['revenue'];
        }

        return $result;
    }

    // Các lịch sắp diễn ra (chưa bắt đầu), gần nhất trước
    public function getUpcoming(int $limit): array
    {
        $statement = $this->conn->prepare(
            "SELECT b.*, c.name AS court_name, " . self::PAID . " AS paid_amount
             FROM bookings b
             INNER JOIN courts c ON c.id = b.court_id
             WHERE b.status IN ('pending', 'confirmed') AND b.start_at > :now
             ORDER BY b.start_at ASC
             LIMIT " . max(1, $limit)
        );
        $statement->execute(['now' => (new DateTimeImmutable())->format('Y-m-d H:i:s')]);

        return $statement->fetchAll();
    }
}