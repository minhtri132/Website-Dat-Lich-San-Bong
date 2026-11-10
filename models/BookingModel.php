<?php
class BookingModel
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = connectDB();
    }

    // Các lịch còn hiệu lực trong một ngày (dùng để vẽ bảng giờ trống)
    public function getBookingsByDate(string $date): array
    {
        $statement = $this->conn->prepare(
            "SELECT id, court_id, user_id, customer_name, start_at, end_at
             FROM bookings
             WHERE status = 'confirmed'
               AND start_at >= :day_start AND start_at < :next_day"
        );
        $statement->execute([
            'day_start' => $date . ' 00:00:00',
            'next_day' => (new DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d 00:00:00'),
        ]);

        return $statement->fetchAll();
    }

    // Lịch sử đặt sân của một khách
    public function getByUser(int $userId): array
    {
        $statement = $this->conn->prepare(
            'SELECT b.*, c.name AS court_name
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
        $sql = 'SELECT b.*, c.name AS court_name
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

    // Kiểm tra khoảng thời gian có bị trùng với lịch khác của sân không
    public function hasOverlap(int $courtId, DateTimeImmutable $startAt, DateTimeImmutable $endAt): bool
    {
        $statement = $this->conn->prepare(
            "SELECT 1 FROM bookings
             WHERE court_id = :court_id
               AND status = 'confirmed'
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

    public function createBooking(
        int $courtId,
        int $userId,
        string $customerName,
        string $phone,
        DateTimeImmutable $startAt,
        int $hours
    ): void {
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

            $insert = $this->conn->prepare(
                "INSERT INTO bookings
                    (court_id, user_id, customer_name, phone, start_at, end_at, price_per_hour, total_price, status)
                 VALUES
                    (:court_id, :user_id, :customer_name, :phone, :start_at, :end_at, :price, :total, 'confirmed')"
            );
            $insert->execute([
                'court_id' => $courtId,
                'user_id' => $userId,
                'customer_name' => $customerName,
                'phone' => $phone,
                'start_at' => $startAt->format('Y-m-d H:i:s'),
                'end_at' => $endAt->format('Y-m-d H:i:s'),
                'price' => $price,
                'total' => (float) $price * $hours,
            ]);

            $this->conn->commit();
        } catch (Throwable $exception) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $exception;
        }
    }

    // $userId = null nghĩa là admin (được hủy mọi lịch chưa bắt đầu)
    public function cancelBooking(int $bookingId, ?int $userId): bool
    {
        $sql = "UPDATE bookings
                SET status = 'cancelled'
                WHERE id = :id AND status = 'confirmed' AND start_at > :now";
        $params = [
            'id' => $bookingId,
            'now' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
        if ($userId !== null) {
            $sql .= ' AND user_id = :user_id';
            $params['user_id'] = $userId;
        }

        $statement = $this->conn->prepare($sql);
        $statement->execute($params);

        return $statement->rowCount() === 1;
    }
        // Thống kê số lịch và tổng tiền của các lịch còn hiệu lực trong khoảng [from, to)
    public function summaryBetween(string $from, string $to): array
    {
        $statement = $this->conn->prepare(
            "SELECT COUNT(*) AS cnt, COALESCE(SUM(total_price), 0) AS revenue
             FROM bookings
             WHERE status = 'confirmed' AND start_at >= :from_at AND start_at < :to_at"
        );
        $statement->execute([
            'from_at' => $from . ' 00:00:00',
            'to_at' => $to . ' 00:00:00',
        ]);

        return $statement->fetch();
    }

    // Tổng tiền theo từng ngày trong khoảng [from, to) -> ['2026-11-08' => 600000, ...]
    public function revenueByDay(string $from, string $to): array
    {
        $statement = $this->conn->prepare(
            "SELECT DATE(start_at) AS day, SUM(total_price) AS revenue
             FROM bookings
             WHERE status = 'confirmed' AND start_at >= :from_at AND start_at < :to_at
             GROUP BY DATE(start_at)"
        );
        $statement->execute([
            'from_at' => $from . ' 00:00:00',
            'to_at' => $to . ' 00:00:00',
        ]);

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
            "SELECT b.*, c.name AS court_name
             FROM bookings b
             INNER JOIN courts c ON c.id = b.court_id
             WHERE b.status = 'confirmed' AND b.start_at > :now
             ORDER BY b.start_at ASC
             LIMIT " . max(1, $limit)
        );
        $statement->execute(['now' => (new DateTimeImmutable())->format('Y-m-d H:i:s')]);

        return $statement->fetchAll();
    }
}