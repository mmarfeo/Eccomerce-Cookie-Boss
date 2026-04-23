<?php
namespace App\Models;

class AdminUser extends BaseModel
{
    protected string $table = 'admin_users';

    public function findByEmail(string $email): ?array
    {
        $row = $this->query(
            "SELECT * FROM admin_users WHERE email = ?", [$email]
        )->fetch();
        return $row ?: null;
    }

    public function updateLastLogin(int $id): void
    {
        $this->query("UPDATE admin_users SET last_login = NOW() WHERE id = ?", [$id]);
    }

    public function create(string $name, string $email, string $role, string $password): int
    {
        $this->query(
            "INSERT INTO admin_users (name, email, password, role) VALUES (?, ?, ?, ?)",
            [$name, $email, password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]), $role]
        );
        return $this->lastInsertId();
    }

    public function updatePassword(int $id, string $password): void
    {
        $this->query(
            "UPDATE admin_users SET password = ? WHERE id = ?",
            [password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]), $id]
        );
    }
}
