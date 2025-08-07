<?php

namespace NL;

class ProductNews {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Lấy tất cả tin tức
    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM product_news ORDER BY id ASC");
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    // Tìm kiếm theo từ khóa
    public function searchNews($keyword) {
        $stmt = $this->pdo->prepare("SELECT * FROM product_news WHERE title LIKE :kw OR summary LIKE :kw ORDER BY id ASC");
        $stmt->execute(['kw' => "%$keyword%"]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    // Lấy tin tức theo ID
    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM product_news WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    // Thêm tin tức
   public function create($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO product_news (title, content, image, created_at)
            VALUES (:title, :content, :image, NOW())
        ");
        return $stmt->execute($data);
    }

    // Cập nhật tin tức
    public function update($data) {
    $stmt = $this->pdo->prepare("
        UPDATE product_news 
        SET title = :title, content = :content, image = :image 
        WHERE id = :id
    ");
    return $stmt->execute($data);
}

    // Xóa tin tức
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM product_news WHERE id = ?");
        return $stmt->execute([$id]);
    }
}