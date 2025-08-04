<?php

namespace NL;

use PDO;

class CartItem
{
    private PDO $db;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    public function add(int $userId, int $productId, int $quantity): void
    {
        // Kiểm tra xem sản phẩm đã có trong giỏ chưa
        $stmt = $this->db->prepare("SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        $existing = $stmt->fetchColumn();

        if ($existing !== false) {
            // Cộng dồn số lượng
            $newQty = $existing + $quantity;
            $updateStmt = $this->db->prepare("UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
            $updateStmt->execute([$newQty, $userId, $productId]);
        } else {
            // Thêm mới
            $insertStmt = $this->db->prepare("INSERT INTO cart_items (user_id, product_id, quantity, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
            $insertStmt->execute([$userId, $productId, $quantity]);
        }
    }


    public function update(int $userId, int $productId, int $quantity): bool
    {
        $stmt = $this->db->prepare("
            UPDATE cart_items SET quantity = :quantity, updated_at = NOW()
            WHERE user_id = :user_id AND product_id = :product_id
        ");
        return $stmt->execute([
            ':quantity' => $quantity,
            ':user_id' => $userId,
            ':product_id' => $productId
        ]);
    }

    public function remove(int $userId, int $productId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM cart_items WHERE user_id = :user_id AND product_id = :product_id
        ");
        return $stmt->execute([
            ':user_id' => $userId,
            ':product_id' => $productId
        ]);
    }

    public function getByUser(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT ci.*, p.name, p.price, p.image
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            WHERE ci.user_id = :user_id
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getItem(int $userId, int $productId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM cart_items
            WHERE user_id = :user_id AND product_id = :product_id
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':product_id' => $productId
        ]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function clear(int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM cart_items WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }
    public function getQuantity(int $userId, int $productId): int
    {
        $stmt = $this->db->prepare("SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        $result = $stmt->fetch();
        return $result ? (int)$result['quantity'] : 0;
    }
}
