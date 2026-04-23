<?php
namespace App\Models;

class Category extends BaseModel
{
    protected string $table = 'categories';

    public function allActive(): array
    {
        return $this->query(
            "SELECT * FROM categories WHERE active = 1 ORDER BY sort_order ASC, name ASC"
        )->fetchAll();
    }

    public function findBySlug(string $slug): ?array
    {
        $row = $this->query("SELECT * FROM categories WHERE slug = ?", [$slug])->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $this->query(
            "INSERT INTO categories (name, slug, description, image, sort_order, active)
             VALUES (:name, :slug, :description, :image, :sort_order, :active)",
            [
                ':name'        => $data['name'],
                ':slug'        => $data['slug'],
                ':description' => $data['description'] ?? null,
                ':image'       => $data['image'] ?? null,
                ':sort_order'  => $data['sort_order'] ?? 0,
                ':active'      => $data['active'] ?? 1,
            ]
        );
        return $this->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];
        $allowed = ['name', 'slug', 'description', 'image', 'sort_order', 'active'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        if (!$fields) return false;

        $params[':id'] = $id;
        return $this->query(
            "UPDATE categories SET " . implode(', ', $fields) . " WHERE id = :id",
            $params
        )->rowCount() > 0;
    }

    public function slugExists(string $slug, int $excludeId = 0): bool
    {
        $count = $this->query(
            "SELECT COUNT(*) FROM categories WHERE slug = ? AND id != ?",
            [$slug, $excludeId]
        )->fetchColumn();
        return (int)$count > 0;
    }

    public function withProductCount(): array
    {
        return $this->query(
            "SELECT c.*, COUNT(p.id) as product_count
             FROM categories c
             LEFT JOIN products p ON p.category_id = c.id AND p.available = 1
             GROUP BY c.id
             ORDER BY c.sort_order ASC, c.name ASC"
        )->fetchAll();
    }
}
