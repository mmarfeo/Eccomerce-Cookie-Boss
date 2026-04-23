<?php
namespace App\Models;

class Order extends BaseModel
{
    protected string $table = 'orders';

    public function create(array $data): int
    {
        $this->query(
            "INSERT INTO orders
                (order_number, customer_id, customer_name, customer_email, customer_phone,
                 customer_address, customer_city, customer_notes,
                 subtotal, shipping_cost, discount, total,
                 status, delivery_method)
             VALUES
                (:order_number, :customer_id, :customer_name, :customer_email, :customer_phone,
                 :customer_address, :customer_city, :customer_notes,
                 :subtotal, :shipping_cost, :discount, :total,
                 :status, :delivery_method)",
            [
                ':order_number'    => $data['order_number'],
                ':customer_id'     => $data['customer_id'] ?? null,
                ':customer_name'   => $data['customer_name'],
                ':customer_email'  => $data['customer_email'],
                ':customer_phone'  => $data['customer_phone'] ?? null,
                ':customer_address'=> $data['customer_address'] ?? null,
                ':customer_city'   => $data['customer_city'] ?? null,
                ':customer_notes'  => $data['customer_notes'] ?? null,
                ':subtotal'        => $data['subtotal'],
                ':shipping_cost'   => $data['shipping_cost'] ?? 0,
                ':discount'        => $data['discount'] ?? 0,
                ':total'           => $data['total'],
                ':status'          => $data['status'] ?? 'pending',
                ':delivery_method' => $data['delivery_method'] ?? 'pickup',
            ]
        );
        return $this->lastInsertId();
    }

    public function addItem(int $orderId, array $item): void
    {
        $this->query(
            "INSERT INTO order_items
                (order_id, product_id, product_name, product_image, unit_price, quantity, subtotal)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $orderId,
                $item['product_id'] ?? null,
                $item['name'],
                $item['image'] ?? null,
                $item['price'],
                $item['quantity'],
                $item['price'] * $item['quantity'],
            ]
        );
    }

    public function updateMercadoPago(int $id, array $mp): void
    {
        $this->query(
            "UPDATE orders SET
                mp_preference_id = :pref_id,
                mp_payment_id    = :pay_id,
                mp_status        = :mp_status,
                mp_status_detail = :mp_detail,
                status           = :status
             WHERE id = :id",
            [
                ':pref_id'   => $mp['preference_id'] ?? null,
                ':pay_id'    => $mp['payment_id'] ?? null,
                ':mp_status' => $mp['mp_status'] ?? null,
                ':mp_detail' => $mp['mp_status_detail'] ?? null,
                ':status'    => $mp['status'],
                ':id'        => $id,
            ]
        );
    }

    public function updateStatus(int $id, string $status, string $notes = '', string $changedBy = 'admin'): void
    {
        $current = $this->findById($id);
        $oldStatus = $current['status'] ?? null;

        $this->query("UPDATE orders SET status = ? WHERE id = ?", [$status, $id]);

        $this->query(
            "INSERT INTO order_status_history (order_id, old_status, new_status, notes, changed_by)
             VALUES (?, ?, ?, ?, ?)",
            [$id, $oldStatus, $status, $notes, $changedBy]
        );
    }

    public function findByOrderNumber(string $orderNumber): ?array
    {
        $row = $this->query(
            "SELECT * FROM orders WHERE order_number = ?", [$orderNumber]
        )->fetch();
        return $row ?: null;
    }

    public function findByMpPreferenceId(string $prefId): ?array
    {
        $row = $this->query(
            "SELECT * FROM orders WHERE mp_preference_id = ?", [$prefId]
        )->fetch();
        return $row ?: null;
    }

    public function findByMpPaymentId(string $paymentId): ?array
    {
        $row = $this->query(
            "SELECT * FROM orders WHERE mp_payment_id = ?", [$paymentId]
        )->fetch();
        return $row ?: null;
    }

    public function getItems(int $orderId): array
    {
        return $this->query(
            "SELECT * FROM order_items WHERE order_id = ?", [$orderId]
        )->fetchAll();
    }

    public function getStatusHistory(int $orderId): array
    {
        return $this->query(
            "SELECT * FROM order_status_history WHERE order_id = ? ORDER BY changed_at ASC",
            [$orderId]
        )->fetchAll();
    }

    public function listAdmin(string $status = '', int $limit = 50, int $offset = 0): array
    {
        $where  = $status ? "WHERE o.status = ?" : "";
        $params = $status ? [$status] : [];
        $params[] = $limit;
        $params[] = $offset;

        return $this->query(
            "SELECT o.*, COUNT(oi.id) as item_count
             FROM orders o
             LEFT JOIN order_items oi ON oi.order_id = o.id
             {$where}
             GROUP BY o.id
             ORDER BY o.created_at DESC
             LIMIT ? OFFSET ?",
            $params
        )->fetchAll();
    }

    public function generateOrderNumber(): string
    {
        $prefix = 'CB';
        $date   = date('Ymd');
        $count  = (int)$this->query(
            "SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()"
        )->fetchColumn();

        return $prefix . '-' . $date . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    // Stats para dashboard
    public function todaySales(): array
    {
        return $this->query(
            "SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as revenue
             FROM orders
             WHERE DATE(created_at) = CURDATE()
               AND status IN ('paid','processing','ready','delivered')"
        )->fetch();
    }

    public function monthSales(): array
    {
        return $this->query(
            "SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as revenue
             FROM orders
             WHERE MONTH(created_at) = MONTH(CURDATE())
               AND YEAR(created_at)  = YEAR(CURDATE())
               AND status IN ('paid','processing','ready','delivered')"
        )->fetch();
    }

    public function pendingCount(): int
    {
        return (int)$this->query(
            "SELECT COUNT(*) FROM orders WHERE status IN ('pending','paid','processing')"
        )->fetchColumn();
    }

    public function updatePoints(int $id, int $points): void
    {
        $this->query("UPDATE orders SET points_earned = ? WHERE id = ?", [$points, $id]);
    }

    public function updateCoupon(int $id, string $code, float $discount): void
    {
        $this->query(
            "UPDATE orders SET coupon_code = ?, coupon_discount = ? WHERE id = ?",
            [$code, $discount, $id]
        );
    }

    public function salesByDay(int $days = 30): array
    {
        return $this->query(
            "SELECT DATE(created_at) as day, COUNT(*) as count, SUM(total) as revenue
             FROM orders
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
               AND status IN ('paid','processing','ready','delivered')
             GROUP BY DATE(created_at)
             ORDER BY day ASC",
            [$days]
        )->fetchAll();
    }
}
