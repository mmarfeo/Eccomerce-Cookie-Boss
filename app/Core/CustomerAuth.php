<?php
namespace App\Core;

class CustomerAuth
{
    private const SESSION_KEY = 'customer_user';

    public static function check(): bool
    {
        return Session::has(self::SESSION_KEY);
    }

    public static function user(): ?array
    {
        return Session::get(self::SESSION_KEY);
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int)$u['id'] : null;
    }

    public static function login(array $customer): void
    {
        Session::regenerate();
        Session::set(self::SESSION_KEY, [
            'id'    => $customer['id'],
            'name'  => $customer['name'],
            'email' => $customer['email'],
        ]);
    }

    public static function logout(): void
    {
        Session::remove(self::SESSION_KEY);
        Session::regenerate();
    }

    public static function require(): void
    {
        if (!self::check()) {
            Session::flash('error', 'Necesitás iniciar sesión para ver esta página.');
            header('Location: ' . BASE_URL . '/cuenta/login');
            exit;
        }
    }
}
