<?php
class CourtModel
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = connectDB();
    }

    public function getAll(): array
    {
        return $this->conn->query('SELECT * FROM courts ORDER BY name')->fetchAll();
    }

    public function getActive(): array
    {
        return $this->conn->query('SELECT * FROM courts WHERE is_active = 1 ORDER BY name')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->conn->prepare('SELECT * FROM courts WHERE id = :id');
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function create(string $name, string $surface, int $price, int $isActive): void
    {
        $statement = $this->conn->prepare(
            'INSERT INTO courts (name, surface, price_per_hour, is_active)
             VALUES (:name, :surface, :price, :is_active)'
        );
        $statement->execute([
            'name' => $name,
            'surface' => $surface,
            'price' => $price,
            'is_active' => $isActive,
        ]);
    }

    public function update(int $id, string $name, string $surface, int $price, int $isActive): void
    {
        $statement = $this->conn->prepare(
            'UPDATE courts
             SET name = :name, surface = :surface, price_per_hour = :price, is_active = :is_active
             WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'name' => $name,
            'surface' => $surface,
            'price' => $price,
            'is_active' => $isActive,
        ]);
    }

    public function toggleActive(int $id): void
    {
        $statement = $this->conn->prepare('UPDATE courts SET is_active = 1 - is_active WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function hasBookings(int $id): bool
    {
        $statement = $this->conn->prepare('SELECT 1 FROM bookings WHERE court_id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        return (bool) $statement->fetchColumn();
    }

    public function delete(int $id): void
    {
        $statement = $this->conn->prepare('DELETE FROM courts WHERE id = :id');
        $statement->execute(['id' => $id]);
    }
}