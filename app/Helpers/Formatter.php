<?php
namespace App\Helpers;

class Formatter
{
    public static function price(float $amount, bool $symbol = true): string
    {
        $formatted = number_format($amount, 0, ',', '.');
        return $symbol ? '$' . $formatted : $formatted;
    }

    public static function date(string $datetime, string $format = 'd/m/Y H:i'): string
    {
        return date($format, strtotime($datetime));
    }

    public static function slug(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = str_replace(
            ['á','é','í','ó','ú','ü','ñ','Á','É','Í','Ó','Ú','Ü','Ñ'],
            ['a','e','i','o','u','u','n','a','e','i','o','u','u','n'],
            $text
        );
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        return trim($text, '-');
    }

    public static function truncate(string $text, int $length = 100, string $ellipsis = '...'): string
    {
        if (mb_strlen($text) <= $length) return $text;
        return mb_substr($text, 0, $length) . $ellipsis;
    }

    public static function statusLabel(string $status): string
    {
        return match($status) {
            'pending'    => 'Pendiente',
            'paid'       => 'Pagado',
            'processing' => 'En preparación',
            'ready'      => 'Listo para entrega',
            'delivered'  => 'Entregado',
            'cancelled'  => 'Cancelado',
            'refunded'   => 'Reembolsado',
            default      => ucfirst($status),
        };
    }

    public static function statusBadge(string $status): string
    {
        $class = match($status) {
            'pending'    => 'warning',
            'paid'       => 'info',
            'processing' => 'primary',
            'ready'      => 'success',
            'delivered'  => 'success',
            'cancelled'  => 'danger',
            'refunded'   => 'secondary',
            default      => 'secondary',
        };
        return '<span class="badge bg-' . $class . '">' . self::statusLabel($status) . '</span>';
    }

    public static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
