<?php

namespace NL;

use PDO;

class Product
{
    private ?PDO $db;

    public function __construct(?PDO $pdo)
    {
        $this->db = $pdo;
    }

    // Lấy tất cả sản phẩm (có tên thương hiệu và danh mục)
    public function getAll()
    {
        $stmt = $this->db->query("
            SELECT p.*, b.name AS brand_name, b.brand_image, c.name AS category_name
            FROM products p
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.deleted_at IS NULL
            ORDER BY p.created_at ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getVisibleProducts()
{
    $stmt = $this->db->prepare("
        SELECT p.*, b.name AS brand_name, c.name AS category_name
        FROM products p
        JOIN brands b ON p.brand_id = b.id
        JOIN categories c ON p.category_id = c.id
        WHERE p.deleted_at IS NULL AND p.is_visible = 1
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}

    // Lấy chi tiết sản phẩm theo id (có tên thương hiệu và danh mục)
    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT p.*, b.name AS brand_name, b.brand_image, c.name AS category_name
            FROM products p
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id = :id AND p.deleted_at IS NULL AND p.is_visible = 1
        ");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // Lấy sản phẩm theo danh mục ID
    public function getProductsByCategoryId($categoryId)
    {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE category_id = :category_id  AND deleted_at IS NULL 
        AND is_visible = 1 ");
        $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getBrands()
    {
        $stmt = $this->db->query("SELECT id, name, brand_image FROM brands ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Lấy sản phẩm theo thương hiệu ID
    public function getProductsByBrand($brandId, $sortOrder = null)
    {
        $order = "ORDER BY p.price ASC";
        if ($sortOrder === 'desc') {
            $order = "ORDER BY p.price DESC";
        }

        $stmt = $this->db->prepare("
        SELECT p.*, b.name AS brand_name,
        (SELECT AVG(r.rating) FROM reviews r WHERE r.product_id = p.id) AS average_rating,
        (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id) AS review_count
        FROM products p
        LEFT JOIN brands b ON p.brand_id = b.id
        WHERE p.brand_id = :brand_id AND p.deleted_at IS NULL AND p.is_visible = 1
        $order
    ");
        $stmt->execute(['brand_id' => $brandId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Lấy sản phẩm theo danh sách ID
    public function getProductsByIds($ids)
    {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND deleted_at IS NULL 
    AND is_visible = 1");
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Tạo sản phẩm mới
    public function create($data)
    {
        $sql = "INSERT INTO products (name, price, image, description, quantity, brand_id, category_id)
                VALUES (:name, :price, :image, :description, :quantity, :brand_id, :category_id)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':name' => $data[':name'],
            ':price' => $data[':price'],
            ':image' => $data[':image'],
            ':description' => $data[':description'],
            ':quantity' => $data[':quantity'],
            ':brand_id' => $data[':brand_id'],
            ':category_id' => $data[':category_id']
        ]);
    }

    // Cập nhật sản phẩm
    public function update($id, $data)
    {
        $sql = "UPDATE products
                SET name = :name, price = :price, image = :image, description = :description,
                    quantity = :quantity, brand_id = :brand_id, category_id = :category_id
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':name' => $data[':name'],
            ':price' => $data[':price'],
            ':image' => $data[':image'],
            ':description' => $data[':description'],
            ':quantity' => $data[':quantity'],
            ':brand_id' => $data[':brand_id'],
            ':category_id' => $data[':category_id']
        ]);
    }

    // Xoá sản phẩm
    public function softDelete($id)
    {
        $stmt = $this->db->prepare("UPDATE products SET deleted_at = NOW() WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getDeleted()
    {
        $stmt = $this->db->prepare("
        SELECT * FROM products 
        WHERE deleted_at IS NOT NULL 
        ORDER BY deleted_at DESC
    ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function restore($id)
    {
        $stmt = $this->db->prepare("UPDATE products SET deleted_at = NULL WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getImages($productId)
    {
        $stmt = $this->db->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Sắp xếp sản phẩm
    public function getAllSorted($sortOrder = null)
    {
        $order = "ORDER BY p.id ASC";
        if ($sortOrder === 'asc') {
            $order = "ORDER BY p.price ASC";
        } elseif ($sortOrder === 'desc') {
            $order = "ORDER BY p.price DESC";
        }

        $stmt = $this->db->query("
            SELECT p.*, b.name AS brand_name, c.name AS category_name,
            (SELECT AVG(r.rating) FROM reviews r WHERE r.product_id = p.id) AS average_rating,
            (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id) AS review_count
            FROM products p
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.deleted_at IS NULL AND p.is_visible = 1
            $order
        ");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Loại bỏ dấu tiếng Việt (phục vụ tìm kiếm)
    public function removeVietnameseTones($str)
    {
        $str = mb_strtolower($str, 'UTF-8');
        $accents = [
            'a' => 'áàảãạăắằẳẵặâấầẩẫậ',
            'd' => 'đ',
            'e' => 'éèẻẽẹêếềểễệ',
            'i' => 'íìỉĩị',
            'o' => 'óòỏõọôốồổỗộơớờởỡợ',
            'u' => 'úùủũụưứừửữự',
            'y' => 'ýỳỷỹỵ'
        ];
        foreach ($accents as $nonAccent => $accentsGroup) {
            $str = preg_replace('/[' . $accentsGroup . ']/u', $nonAccent, $str);
        }
        return $str;
    }

    // Tìm kiếm sản phẩm (có lọc theo tên và mô tả)
    public function searchProducts($searchTerm)
    {
        $normalized = $this->removeVietnameseTones($searchTerm);
        $normalized = mb_strtolower($normalized, 'UTF-8');

        $allProducts = $this->getAll();
        $results = [];

        foreach ($allProducts as $product) {
            $nameNorm = mb_strtolower($this->removeVietnameseTones($product->name), 'UTF-8');

            if (strpos($nameNorm, $normalized) !== false) {
                $results[] = $product;
            }
        }

        return $results;
    }

    public function reduceStock($product_id, $quantity)
    {
        $stmt = $this->db->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
        return $stmt->execute([$quantity, $product_id]);
    }
    public function getAverageRating($productId)
    {
        $stmt = $this->db->prepare("
        SELECT AVG(rating) as avg_rating
        FROM reviews
        WHERE product_id = ?
    ");
        $stmt->execute([$productId]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return round($result->avg_rating, 1); // ví dụ: 4.2
    }

    public function getRatingStats($productId)
    {
        $stmt = $this->db->prepare("
        SELECT COUNT(*) AS total_reviews, AVG(rating) AS avg_rating
        FROM reviews
        WHERE product_id = ?
    ");
        $stmt->execute([$productId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
    public function getTotalProducts(): int
{
    $stmt = $this->db->query("SELECT COUNT(*) FROM products WHERE deleted_at IS NULL");
    return (int) $stmt->fetchColumn();
}

}
