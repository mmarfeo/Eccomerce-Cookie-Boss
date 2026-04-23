<?php
namespace App\Helpers;

use App\Core\Database;
use App\Models\Setting;

class Loyalty
{
    // ── Configuración desde settings ──────────────────────────
    public static function config(): array
    {
        $s = new Setting();
        return [
            'enabled'       => $s->get('loyalty_enabled', '1') === '1',
            'per_peso'      => (float)$s->get('loyalty_points_per_peso', '1'),
            'redeem_rate'   => (int)$s->get('loyalty_redeem_rate', '100'),   // pts por $1
            'min_redeem'    => (int)$s->get('loyalty_min_redeem', '500'),
            'silver_min'    => (int)$s->get('loyalty_silver_min', '5000'),
            'gold_min'      => (int)$s->get('loyalty_gold_min', '15000'),
            'plat_min'      => (int)$s->get('loyalty_plat_min', '40000'),
        ];
    }

    // ── Calcular y acumular puntos tras una compra ────────────
    public static function earnPoints(array $order): int
    {
        $cfg = self::config();
        if (!$cfg['enabled'] || !$order['customer_id']) return 0;

        $subtotal = (float)$order['subtotal'] - (float)($order['coupon_discount'] ?? 0);
        $points   = (int)floor($subtotal * $cfg['per_peso']);
        if ($points <= 0) return 0;

        $db      = Database::getInstance();
        $balance = self::getBalance((int)$order['customer_id']) + $points;

        $db->prepare("INSERT INTO loyalty_points (customer_id, order_id, type, points, balance_after, description) VALUES (?,?,?,?,?,?)")
           ->execute([$order['customer_id'], $order['id'], 'earn', $points, $balance, "Compra #{$order['order_number']}"]);

        $db->prepare("UPDATE customers SET loyalty_points = ?, loyalty_tier = ? WHERE id = ?")
           ->execute([$balance, self::tier($balance, $cfg), $order['customer_id']]);

        return $points;
    }

    // ── Calcular descuento si se canjean puntos ───────────────
    public static function calculateRedeem(int $customerId, int $pointsToRedeem): array
    {
        $cfg     = self::config();
        $balance = self::getBalance($customerId);

        if (!$cfg['enabled'] || $pointsToRedeem < $cfg['min_redeem'] || $pointsToRedeem > $balance) {
            return ['valid' => false, 'discount' => 0, 'points' => 0];
        }

        // Redondear al múltiplo de rate
        $validPoints = intdiv($pointsToRedeem, $cfg['redeem_rate']) * $cfg['redeem_rate'];
        $discount    = $validPoints / $cfg['redeem_rate'];

        return ['valid' => true, 'discount' => $discount, 'points' => $validPoints];
    }

    // ── Registrar canje ───────────────────────────────────────
    public static function redeemPoints(int $customerId, int $orderId, int $points, float $discount): void
    {
        $db      = Database::getInstance();
        $balance = self::getBalance($customerId) - $points;
        $cfg     = self::config();

        $db->prepare("INSERT INTO loyalty_points (customer_id, order_id, type, points, balance_after, description) VALUES (?,?,?,?,?,?)")
           ->execute([$customerId, $orderId, 'redeem', -$points, max(0, $balance), "Canje de {$points} pts = \${$discount}"]);

        $db->prepare("UPDATE customers SET loyalty_points = ?, loyalty_tier = ? WHERE id = ?")
           ->execute([max(0, $balance), self::tier(max(0, $balance), $cfg), $customerId]);
    }

    // ── Ajuste manual (desde admin) ───────────────────────────
    public static function adjust(int $customerId, int $points, string $reason): void
    {
        $db      = Database::getInstance();
        $balance = self::getBalance($customerId) + $points;
        $balance = max(0, $balance);
        $cfg     = self::config();

        $db->prepare("INSERT INTO loyalty_points (customer_id, order_id, type, points, balance_after, description) VALUES (?,NULL,?,?,?,?)")
           ->execute([$customerId, 'adjust', $points, $balance, $reason]);

        $db->prepare("UPDATE customers SET loyalty_points = ?, loyalty_tier = ? WHERE id = ?")
           ->execute([$balance, self::tier($balance, $cfg), $customerId]);
    }

    // ── Balance actual ────────────────────────────────────────
    public static function getBalance(int $customerId): int
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT loyalty_points FROM customers WHERE id = ?");
        $stmt->execute([$customerId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? (int)$row['loyalty_points'] : 0;
    }

    // ── Historial de movimientos ──────────────────────────────
    public static function history(int $customerId): array
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT lp.*, o.order_number FROM loyalty_points lp LEFT JOIN orders o ON o.id = lp.order_id WHERE lp.customer_id = ? ORDER BY lp.created_at DESC LIMIT 50");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ── Tier según puntos ─────────────────────────────────────
    public static function tier(int $balance, array $cfg): string
    {
        if ($balance >= $cfg['plat_min'])   return 'platinum';
        if ($balance >= $cfg['gold_min'])   return 'gold';
        if ($balance >= $cfg['silver_min']) return 'silver';
        return 'bronze';
    }

    public static function tierLabel(string $tier): string
    {
        return match($tier) {
            'platinum' => '💎 Platinum',
            'gold'     => '🥇 Gold',
            'silver'   => '🥈 Silver',
            default    => '🥉 Bronze',
        };
    }

    public static function tierColor(string $tier): string
    {
        return match($tier) {
            'platinum' => '#7c3aed',
            'gold'     => '#d97706',
            'silver'   => '#6b7280',
            default    => '#92400e',
        };
    }
}
