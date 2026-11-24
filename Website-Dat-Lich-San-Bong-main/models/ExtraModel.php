<?php
class ExtraModel
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = connectDB();
    }

    public function getAll(): array
    {
        return $this->conn->query(
            'SELECT * FROM extra_products ORDER BY category, name'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->conn->prepare('SELECT * FROM extra_products WHERE id = :id');
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function create(string $category, string $name, int $price, int $isActive): void
    {
        $code = $category . '_' . bin2hex(random_bytes(6));
        $statement = $this->conn->prepare(
            'INSERT INTO extra_products (code, category, name, price, is_active)
             VALUES (:code, :category, :name, :price, :is_active)'
        );
        $statement->execute([
            'code' => $code,
            'category' => $category,
            'name' => $name,
            'price' => $price,
            'is_active' => $isActive,
        ]);
    }

    public function update(int $id, string $category, string $name, int $price, int $isActive): void
    {
        $statement = $this->conn->prepare(
            'UPDATE extra_products
             SET category = :category, name = :name, price = :price, is_active = :is_active
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'category' => $category,
            'name' => $name,
            'price' => $price,
            'is_active' => $isActive,
        ]);
    }

    public function toggleActive(int $id): void
    {
        $statement = $this->conn->prepare(
            'UPDATE extra_products SET is_active = 1 - is_active WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
    }

    public function hasOrders(int $id): bool
    {
        $statement = $this->conn->prepare(
            'SELECT 1 FROM booking_extras WHERE product_id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        return (bool) $statement->fetchColumn();
    }

    public function delete(int $id): void
    {
        $statement = $this->conn->prepare('DELETE FROM extra_products WHERE id = :id');
        $statement->execute(['id' => $id]);
    }
}
