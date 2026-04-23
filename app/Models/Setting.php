<?php
namespace App\Models;

class Setting extends BaseModel
{
    protected string $table = 'settings';

    private static array $cache = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (!isset(self::$cache[$key])) {
            $row = $this->query(
                "SELECT setting_value FROM settings WHERE setting_key = ?", [$key]
            )->fetch();
            self::$cache[$key] = $row ? $row['setting_value'] : null;
        }
        return self::$cache[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->query(
            "INSERT INTO settings (setting_key, setting_value)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
            [$key, $value]
        );
        self::$cache[$key] = $value;
    }

    public function all(): array
    {
        $rows   = $this->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['setting_key']] = $row['setting_value'];
        }
        return $result;
    }

    public function saveMany(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }
}
