<?php
namespace App\Models;

class Customer extends BaseModel
{
    protected string $table = 'customers';

    public function findOrCreate(array $data): int
    {
        $existing = $this->query(
            "SELECT id FROM customers WHERE email = ?", [$data['email']]
        )->fetch();

        if ($existing) {
            $this->query(
                "UPDATE customers SET
                    name    = ?,
                    phone   = COALESCE(?, phone),
                    address = COALESCE(?, address),
                    city    = COALESCE(?, city),
                    total_orders = total_orders + 1,
                    total_spent  = total_spent + ?
                 WHERE id = ?",
                [
                    $data['name'],
                    $data['phone'] ?? null,
                    $data['address'] ?? null,
                    $data['city'] ?? null,
                    $data['order_total'] ?? 0,
                    $existing['id'],
                ]
            );
            return (int)$existing['id'];
        }

        $this->query(
            "INSERT INTO customers (name, email, phone, address, city, total_orders, total_spent)
             VALUES (?, ?, ?, ?, ?, 1, ?)",
            [
                $data['name'],
                $data['email'],
                $data['phone'] ?? null,
                $data['address'] ?? null,
                $data['city'] ?? null,
                $data['order_total'] ?? 0,
            ]
        );
        return $this->lastInsertId();
    }

    // ── Auth de clientes ──────────────────────────────────────

    public function findByEmail(string $email): ?array
    {
        $row = $this->query("SELECT * FROM customers WHERE email = ?", [$email])->fetch();
        return $row ?: null;
    }

    public function register(array $data): int
    {
        $this->query(
            "INSERT INTO customers (name, email, phone, password, total_orders, total_spent)
             VALUES (?, ?, ?, ?, 0, 0)",
            [
                $data['name'],
                $data['email'],
                $data['phone'] ?? null,
                password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            ]
        );
        return $this->lastInsertId();
    }

    public function updateProfile(int $id, array $data): void
    {
        $this->query(
            "UPDATE customers SET name = ?, phone = ?, address = ?, city = ? WHERE id = ?",
            [$data['name'], $data['phone'] ?? null, $data['address'] ?? null, $data['city'] ?? null, $id]
        );
    }

    public function updatePassword(int $id, string $newPassword): void
    {
        $this->query(
            "UPDATE customers SET password = ? WHERE id = ?",
            [password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]), $id]
        );
    }

    public function updateLastLogin(int $id): void
    {
        $this->query("UPDATE customers SET last_login = NOW() WHERE id = ?", [$id]);
    }

    // ── Puntos de fidelidad ───────────────────────────────────

    public function getLoyaltyHistory(int $customerId): array
    {
        return $this->query(
            "SELECT lp.*, o.order_number FROM loyalty_points lp
             LEFT JOIN orders o ON o.id = lp.order_id
             WHERE lp.customer_id = ?
             ORDER BY lp.created_at DESC LIMIT 30",
            [$customerId]
        )->fetchAll();
    }

    public function listWithStats(): array
    {
        return $this->query(
            "SELECT * FROM customers ORDER BY total_orders DESC, created_at DESC"
        )->fetchAll();
    }

    public function getOrders(int $customerId): array
    {
        return $this->query(
            "SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC",
            [$customerId]
        )->fetchAll();
    }
}
