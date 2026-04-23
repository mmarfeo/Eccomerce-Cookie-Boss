<?php
namespace App\Core;

class Auth
{
    private const SESSION_KEY      = 'admin_user';
    private const LOGIN_ATTEMPTS   = 'login_attempts';
    private const MAX_ATTEMPTS     = 5;
    private const LOCKOUT_MINUTES  = 10;

    public static function check(): bool
    {
        return Session::has(self::SESSION_KEY);
    }

    public static function user(): ?array
    {
        return Session::get(self::SESSION_KEY);
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set(self::SESSION_KEY, [
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ]);
        self::clearAttempts();
    }

    public static function logout(): void
    {
        Session::remove(self::SESSION_KEY);
        Session::regenerate();
    }

    public static function require(): void
    {
        if (!self::check()) {
            Session::flash('error', 'Debés iniciar sesión para acceder al panel.');
            header('Location: ' . BASE_URL . '/admin/login');
            exit;
        }
    }

    // Rate limiting para login
    public static function isLocked(): bool
    {
        $data = Session::get(self::LOGIN_ATTEMPTS, []);
        if (empty($data)) return false;

        if ($data['count'] >= self::MAX_ATTEMPTS) {
            $lockout = (int)$data['last_attempt'] + (self::LOCKOUT_MINUTES * 60);
            if (time() < $lockout) {
                return true;
            }
            self::clearAttempts();
        }
        return false;
    }

    public static function recordFailedAttempt(): void
    {
        $data = Session::get(self::LOGIN_ATTEMPTS, ['count' => 0, 'last_attempt' => 0]);
        $data['count']++;
        $data['last_attempt'] = time();
        Session::set(self::LOGIN_ATTEMPTS, $data);
    }

    public static function clearAttempts(): void
    {
        Session::remove(self::LOGIN_ATTEMPTS);
    }

    public static function remainingLockout(): int
    {
        $data = Session::get(self::LOGIN_ATTEMPTS, []);
        if (empty($data)) return 0;
        $lockout = (int)$data['last_attempt'] + (self::LOCKOUT_MINUTES * 60);
        return max(0, $lockout - time());
    }
}
