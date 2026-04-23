<?php
namespace App\Helpers;

use App\Core\Session;

class Cart
{
    private const KEY = 'shopping_cart';

    public static function get(): array
    {
        return Session::get(self::KEY, []);
    }

    public static function add(array $product, int $quantity = 1): void
    {
        $cart = self::get();
        $id   = (int)$product['id'];

        if (isset($cart[$id])) {
            $cart[$id]['quantity'] += $quantity;
        } else {
            $cart[$id] = [
                'id'       => $id,
                'name'     => $product['name'],
                'slug'     => $product['slug'],
                'price'    => (float)$product['price'],
                'image'    => $product['main_image'] ?? null,
                'quantity' => $quantity,
            ];
        }
        Session::set(self::KEY, $cart);
    }

    public static function update(int $productId, int $quantity): void
    {
        $cart = self::get();
        if (isset($cart[$productId])) {
            if ($quantity <= 0) {
                unset($cart[$productId]);
            } else {
                $cart[$productId]['quantity'] = $quantity;
            }
            Session::set(self::KEY, $cart);
        }
    }

    public static function remove(int $productId): void
    {
        $cart = self::get();
        unset($cart[$productId]);
        Session::set(self::KEY, $cart);
    }

    public static function clear(): void
    {
        Session::remove(self::KEY);
    }

    public static function count(): int
    {
        return array_sum(array_column(self::get(), 'quantity'));
    }

    public static function subtotal(): float
    {
        $total = 0;
        foreach (self::get() as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }

    public static function total(float $shipping = 0, float $discount = 0): float
    {
        return self::subtotal() + $shipping - $discount;
    }

    public static function isEmpty(): bool
    {
        return empty(self::get());
    }

    public static function toArray(float $shipping = 0): array
    {
        $subtotal = self::subtotal();
        $total    = $subtotal + $shipping;

        return [
            'items'    => array_values(self::get()),
            'count'    => self::count(),
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total'    => $total,
        ];
    }
}
