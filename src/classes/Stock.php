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
    public function updateStockQuantity($product_id, $quantity_change, $change_type)
    {
        $currentProduct = $this->getProductById($product_id);

        if (!$currentProduct) {
            return false; // Sản phẩm không tồn tại
        }

        $new_quantity = ($change_type === 'in') ? $currentProduct->quantity + $quantity_change : $currentProduct->quantity - $quantity_change;

        if ($new_quantity < 0) {
            return false; // Không đủ sản phẩm để xuất kho
        }

        $stmt = $this->db->prepare("UPDATE products SET quantity = :quantity WHERE id = :id");
        $stmt->bindParam(':quantity', $new_quantity, PDO::PARAM_INT);
        $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
        $stmt->execute();

        // Lưu lịch sử thay đổi vào bảng stock_history
        $this->logStockChange($product_id, $quantity_change, $change_type);

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
    private function logStockChange($product_id, $quantity_change, $change_type)
    {
        $stmt = $this->db->prepare("INSERT INTO stock_history (product_id, change_quantity, change_type, change_date) 
                                    VALUES (:product_id, :change_quantity, :change_type, NOW())");
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->bindParam(':change_quantity', $quantity_change, PDO::PARAM_INT);
        $stmt->bindParam(':change_type', $change_type, PDO::PARAM_STR);
        $stmt->execute();
    }

    // Lấy tất cả lịch sử thay đổi kho
    public function getStockHistory()
    {
        $stmt = $this->db->query("SELECT sh.*, p.name AS product_name FROM stock_history sh JOIN products p ON sh.product_id = p.id ORDER BY sh.change_date DESC");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
?>
