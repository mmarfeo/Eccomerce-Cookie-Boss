<?php
namespace App\Models;

class Product extends BaseModel
{
    protected string $table = 'products';

    public function allAvailable(int $categoryId = 0): array
    {
        if ($categoryId) {
            return $this->query(
                "SELECT p.*, c.name as category_name, c.slug as category_slug
                 FROM products p
                 LEFT JOIN categories c ON c.id = p.category_id
                 WHERE p.available = 1 AND p.category_id = ?
                 ORDER BY p.sort_order ASC, p.name ASC",
                [$categoryId]
            )->fetchAll();
        }
        return $this->query(
            "SELECT p.*, c.name as category_name, c.slug as category_slug
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.available = 1
             ORDER BY p.sort_order ASC, p.name ASC"
        )->fetchAll();
    }

    public function featured(int $limit = 6): array
    {
        return $this->query(
            "SELECT p.*, c.name as category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.available = 1 AND p.featured = 1
             ORDER BY p.sort_order ASC LIMIT ?",
            [$limit]
        )->fetchAll();
    }

    public function findBySlug(string $slug): ?array
    {
        $row = $this->query(
            "SELECT p.*, c.name as category_name, c.slug as category_slug
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.slug = ? AND p.available = 1",
            [$slug]
        )->fetch();
        return $row ?: null;
    }

    public function findBySlugAdmin(string $slug): ?array
    {
        $row = $this->query(
            "SELECT p.*, c.name as category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.slug = ?",
            [$slug]
        )->fetch();
        return $row ?: null;
    }

    public function withCategory(): array
    {
        return $this->query(
            "SELECT p.*, c.name as category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             ORDER BY p.sort_order ASC, p.name ASC"
        )->fetchAll();
    }

    public function create(array $data): int
    {
        $this->query(
            "INSERT INTO products
                (category_id, name, slug, short_desc, description, ingredients,
                 price, compare_price, main_image, featured, available, sort_order)
             VALUES
                (:category_id, :name, :slug, :short_desc, :description, :ingredients,
                 :price, :compare_price, :main_image, :featured, :available, :sort_order)",
            [
                ':category_id'   => $data['category_id'] ?: null,
                ':name'          => $data['name'],
                ':slug'          => $data['slug'],
                ':short_desc'    => $data['short_desc'] ?? null,
                ':description'   => $data['description'] ?? null,
                ':ingredients'   => $data['ingredients'] ?? null,
                ':price'         => $data['price'],
                ':compare_price' => !empty($data['compare_price']) ? $data['compare_price'] : null,
                ':main_image'    => $data['main_image'] ?? null,
                ':featured'      => $data['featured'] ?? 0,
                ':available'     => $data['available'] ?? 1,
                ':sort_order'    => $data['sort_order'] ?? 0,
            ]
        );
        return $this->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields  = [];
        $params  = [];
        $allowed = ['category_id','name','slug','short_desc','description','ingredients',
                    'price','compare_price','main_image','featured','available','sort_order'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }
        if (!$fields) return false;

        $params[':id'] = $id;
        return $this->query(
            "UPDATE products SET " . implode(', ', $fields) . " WHERE id = :id",
            $params
        )->rowCount() >= 0;
    }

    public function toggle(int $id): bool
    {
        return $this->query(
            "UPDATE products SET available = NOT available WHERE id = ?", [$id]
        )->rowCount() > 0;
    }

    public function slugExists(string $slug, int $excludeId = 0): bool
    {
        return (int)$this->query(
            "SELECT COUNT(*) FROM products WHERE slug = ? AND id != ?",
            [$slug, $excludeId]
        )->fetchColumn() > 0;
    }

    public function getImages(int $productId): array
    {
        return $this->query(
            "SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC",
            [$productId]
        )->fetchAll();
    }

    public function addImage(int $productId, string $filename, string $altText = ''): int
    {
        $this->query(
            "INSERT INTO product_images (product_id, filename, alt_text) VALUES (?, ?, ?)",
            [$productId, $filename, $altText]
        );
        return $this->lastInsertId();
    }

    public function deleteImage(int $imageId): ?string
    {
        $img = $this->query("SELECT filename FROM product_images WHERE id = ?", [$imageId])->fetch();
        if (!$img) return null;
        $this->query("DELETE FROM product_images WHERE id = ?", [$imageId]);
        return $img['filename'];
    }

    public function getBestSellers(int $limit = 5): array
    {
        return $this->query(
            "SELECT p.id, p.name, p.main_image, p.price,
                    SUM(oi.quantity) as total_sold,
                    SUM(oi.subtotal) as total_revenue
             FROM order_items oi
             JOIN products p ON p.id = oi.product_id
             JOIN orders o ON o.id = oi.order_id AND o.status IN ('paid','processing','ready','delivered')
             GROUP BY p.id
             ORDER BY total_sold DESC
             LIMIT ?",
            [$limit]
        )->fetchAll();
    }
}
