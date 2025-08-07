<?php

namespace NL;

use PDO;

class Stock
{
    private ?PDO $db;

    public function __construct(?PDO $pdo)
    {
        $this->db = $pdo;
    }

    // Thêm sản phẩm mới vào kho
    public function addProductToStock($product_id, $quantity)
    {
        $stmt = $this->db->prepare("UPDATE products SET quantity = quantity + :quantity WHERE id = :id");
        $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
        $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        return $stmt->execute();
    }

    // Cập nhật số lượng sản phẩm khi thay đổi kho
    public function updateStockQuantity($product_id, $quantity_change, $change_type, $import_price = null, $export_price = null, $user_id = null, $log = true)
    {
        $currentProduct = $this->getProductById($product_id);

        if (!$currentProduct) return false;

        $new_quantity = ($change_type === 'in')
            ? $currentProduct->quantity + $quantity_change
            : $currentProduct->quantity - $quantity_change;

        if ($new_quantity < 0) return false;

        $stmt = $this->db->prepare("UPDATE products SET quantity = :quantity WHERE id = :id");
        $stmt->bindParam(':quantity', $new_quantity, PDO::PARAM_INT);
        $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
        $stmt->execute();

        // Ghi log nếu cần
        if ($log) {
            $user_id = $_SESSION['user_id'] ?? $user_id;
            $this->logStockChange($product_id, $quantity_change, $change_type, $import_price, $export_price, $user_id);
        }

        return true;
    }

    // Lấy thông tin sản phẩm theo id
    private function getProductById($product_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // Lưu lịch sử thay đổi kho vào bảng stock_history
    private function logStockChange($product_id, $quantity_change, $change_type, $import_price = null, $export_price = null, $user_id = null)
    {
        $stmt = $this->db->prepare("
        INSERT INTO stock_history 
        (product_id, change_quantity, change_type, change_date, import_price, export_price, user_id)
        VALUES (:product_id, :change_quantity, :change_type, NOW(), :import_price, :export_price, :user_id)
    ");
        $stmt->execute([
            ':product_id' => $product_id,
            ':change_quantity' => $quantity_change,
            ':change_type' => $change_type,
            ':import_price' => $change_type === 'in' ? $import_price : null,
            ':export_price' => $change_type === 'out' ? $export_price : null,
            ':user_id' => $user_id,
        ]);
    }


    // Lấy tất cả lịch sử thay đổi kho kèm tên người thao tác
    public function getStockHistory()
    {
        $stmt = $this->db->query("
            SELECT sh.*, p.name AS product_name, u.username AS user_name
            FROM stock_history sh
            JOIN products p ON sh.product_id = p.id
            LEFT JOIN users u ON sh.user_id = u.id
            ORDER BY sh.change_date DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    public function getAllStockHistoryGroupedByProduct()
    {
        $stmt = $this->db->prepare("
        SELECT sh.*, p.name as product_name, u.username as user_name
        FROM stock_history sh
        JOIN products p ON sh.product_id = p.id
        LEFT JOIN users u ON sh.user_id = u.id
        ORDER BY sh.change_date DESC
    ");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row->product_id][] = $row;
        }
        return $grouped;
    }
}
