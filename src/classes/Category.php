<?php
namespace NL;

use PDO;

class Category
{
    private ?PDO $db;

    public function __construct(?PDO $pdo)
    {
        $this->db = $pdo;
    }

    // Lấy tất cả danh mục
    public function getAll()
    {
        $stmt = $this->db->query("SELECT * FROM categories ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Lấy danh mục theo id
    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // Tạo mới danh mục
    public function create($name, $categoryImage = null)
    {
        $stmt = $this->db->prepare("
            INSERT INTO categories (name, category_image) 
            VALUES (:name, :category_image)
        ");
        return $stmt->execute([
            ':name' => $name,
            ':category_image' => $categoryImage,
        ]);
    }

    // Cập nhật danh mục
    public function update($id, $name, $categoryImage = null)
    {
        $stmt = $this->db->prepare("
            UPDATE categories 
            SET name = :name, category_image = :category_image 
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':category_image' => $categoryImage,
        ]);
    }

    // Xoá danh mục
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM categories WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
