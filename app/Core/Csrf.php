<?php
namespace App\Core;

class Csrf
{
    public static function token(): string
    {
        if (!Session::has(CSRF_TOKEN_KEY)) {
            Session::set(CSRF_TOKEN_KEY, bin2hex(random_bytes(32)));
        }
        return Session::get(CSRF_TOKEN_KEY);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . self::token() . '">';
    }

    public static function verify(): bool
    {
        $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return hash_equals(Session::get(CSRF_TOKEN_KEY, ''), $token);
    }

    public static function verifyOrFail(): void
    {
        if (!self::verify()) {
            http_response_code(419);
            die('Token CSRF inválido. Por favor recargá la página e intentá de nuevo.');
        }
    }
}
