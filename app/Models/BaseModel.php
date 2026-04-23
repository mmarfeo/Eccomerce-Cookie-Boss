<?php
namespace App\Models;

use App\Core\Database;
use PDO;

abstract class BaseModel
{
    protected PDO $db;
    protected string $table;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    protected function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->query("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
        $row  = $stmt->fetch();
        return $row ?: null;
    }

    public function findAll(string $orderBy = 'id ASC'): array
    {
        return $this->query("SELECT * FROM {$this->table} ORDER BY {$orderBy}")->fetchAll();
    }

    public function delete(int $id): bool
    {
        return $this->query("DELETE FROM {$this->table} WHERE id = ?", [$id])->rowCount() > 0;
    }

    public function count(string $where = '', array $params = []): int
    {
        $sql  = "SELECT COUNT(*) FROM {$this->table}";
        if ($where) $sql .= " WHERE {$where}";
        return (int) $this->query($sql, $params)->fetchColumn();
    }

    protected function lastInsertId(): int
    {
        return (int) $this->db->lastInsertId();
    }
}
