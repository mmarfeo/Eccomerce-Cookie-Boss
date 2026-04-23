<?php
namespace App\Models;

class Coupon extends BaseModel
{
    protected string $table = 'coupons';

    public function findByCode(string $code): ?array
    {
        $row = $this->query(
            "SELECT * FROM coupons WHERE code = ? AND active = 1
             AND (expires_at IS NULL OR expires_at > NOW())
             AND (max_uses IS NULL OR used_count < max_uses)",
            [strtoupper(trim($code))]
        )->fetch();
        return $row ?: null;
    }

    public function calculateDiscount(array $coupon, float $subtotal): float
    {
        if ($subtotal < (float)$coupon['min_order']) return 0;

        if ($coupon['type'] === 'percent') {
            return round($subtotal * ((float)$coupon['value'] / 100), 2);
        }
        return min((float)$coupon['value'], $subtotal);
    }

    public function incrementUsage(int $id): void
    {
        $this->query("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?", [$id]);
    }

    public function create(array $data): int
    {
        $this->query(
            "INSERT INTO coupons (code, description, type, value, min_order, max_uses, active, expires_at)
             VALUES (:code, :desc, :type, :value, :min_order, :max_uses, :active, :expires_at)",
            [
                ':code'       => strtoupper(trim($data['code'])),
                ':desc'       => $data['description'] ?? null,
                ':type'       => $data['type'] ?? 'percent',
                ':value'      => $data['value'],
                ':min_order'  => $data['min_order'] ?? 0,
                ':max_uses'   => !empty($data['max_uses']) ? (int)$data['max_uses'] : null,
                ':active'     => $data['active'] ?? 1,
                ':expires_at' => !empty($data['expires_at']) ? $data['expires_at'] : null,
            ]
        );
        return $this->lastInsertId();
    }

    public function toggle(int $id): void
    {
        $this->query("UPDATE coupons SET active = NOT active WHERE id = ?", [$id]);
    }
}
