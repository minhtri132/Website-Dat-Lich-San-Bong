<?php
class UserModel
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = connectDB();
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->conn->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        return $statement->fetch() ?: null;
    }

    public function create(string $fullName, string $phone, string $email, string $passwordHash): void
    {
        $statement = $this->conn->prepare(
            "INSERT INTO users (full_name, phone, email, password, role)
             VALUES (:full_name, :phone, :email, :password, 'customer')"
        );
        $statement->execute([
            'full_name' => $fullName,
            'phone' => $phone,
            'email' => $email,
            'password' => $passwordHash,
        ]);
    }
        public function countCustomers(): int
    {
        return (int) $this->conn->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
    }

    // Danh sách khách hàng kèm số lần đặt và tổng tiền; $keyword tìm theo tên, số điện thoại, email
       public function searchCustomers(?string $keyword): array
    {
        $sql = "SELECT u.id, u.full_name, u.phone, u.email, u.created_at,
                       COUNT(b.id) AS booking_count,
                       COALESCE((SELECT SUM(p.amount) FROM payments p
                                 INNER JOIN bookings b2 ON b2.id = p.booking_id
                                 WHERE b2.user_id = u.id), 0) AS total_spent
                FROM users u
                LEFT JOIN bookings b ON b.user_id = u.id
                WHERE u.role = 'customer'";
        $params = [];

        if ($keyword !== null && $keyword !== '') {
            $like = '%' . addcslashes($keyword, '%_\\\\') . '%';
            $sql .= ' AND (u.full_name LIKE :k1 OR u.phone LIKE :k2 OR u.email LIKE :k3)';
            $params = ['k1' => $like, 'k2' => $like, 'k3' => $like];
        }
        $sql .= ' GROUP BY u.id, u.full_name, u.phone, u.email, u.created_at ORDER BY u.created_at DESC';

        $statement = $this->conn->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }
}