<?php
namespace NL;

use PDO;

class Brand
{
    private ?PDO $db;

    public function __construct(?PDO $pdo)
    {
        $this->db = $pdo;
    }

    // Lấy tất cả thương hiệu
    public function getAll()
    {
        $stmt = $this->db->query("SELECT * FROM brands ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Lấy thương hiệu theo id
    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM brands WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // Tạo mới thương hiệu
    public function create($name, $brandImage = null)
    {
        $stmt = $this->db->prepare("
            INSERT INTO brands (name, brand_image) 
            VALUES (:name, :brand_image)
        ");
        return $stmt->execute([
            ':name' => $name,
            ':brand_image' => $brandImage,
        ]);
    }

    // Cập nhật thương hiệu
    public function update($id, $name, $brandImage = null)
    {
        $stmt = $this->db->prepare("
            UPDATE brands 
            SET name = :name, brand_image = :brand_image 
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':brand_image' => $brandImage,
        ]);
    }

    // Xoá thương hiệu
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM brands WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
