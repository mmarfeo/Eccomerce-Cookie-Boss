<?php
namespace App\Models;

class PasswordReset extends BaseModel
{
    protected string $table = 'password_resets';

    public function create(string $email): string
    {
        // Invalidar tokens anteriores
        $this->query("DELETE FROM password_resets WHERE email = ?", [$email]);

        $token     = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $this->query(
            "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)",
            [$email, $token, $expiresAt]
        );
        return $token;
    }

    public function findValid(string $token): ?array
    {
        $row = $this->query(
            "SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()",
            [$token]
        )->fetch();
        return $row ?: null;
    }

    public function markUsed(string $token): void
    {
        $this->query("UPDATE password_resets SET used = 1 WHERE token = ?", [$token]);
    }

    public function cleanup(): void
    {
        $this->query("DELETE FROM password_resets WHERE expires_at < NOW() OR used = 1");
    }
}
