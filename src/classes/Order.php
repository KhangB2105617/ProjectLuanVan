<?php

namespace NL;

use PDO;

class Order
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Lấy tất cả đơn đặt hàng
    public function getAllOrders()
    {
        $stmt = $this->pdo->query("SELECT id, customer_name, customer_email, customer_address, customer_phone, product_name, quantity, price, status FROM orders");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    public function updateOrderStatus($orderId, $newStatus) {
        // Cập nhật trạng thái đơn hàng
        $stmt = $this->pdo->prepare("UPDATE orders SET status = :status WHERE id = :orderId");
        $stmt->execute([':status' => $newStatus, ':orderId' => $orderId]);
    
        // Kiểm tra xem có bản ghi nào được cập nhật không
        if ($stmt->rowCount() > 0) {
            return true; // Thành công
        } else {
            return false; // Không có dòng nào bị ảnh hưởng
        }
    }

    // Lưu đơn hàng vào cơ sở dữ liệu
    public function createOrder($name,$username , $email, $address, $phone, $productName, $quantity, $price, $status) {
        $stmt = $this->pdo->prepare("INSERT INTO orders (customer_name,username , customer_email, customer_address, customer_phone, product_name, quantity, price, status) 
                                     VALUES (:name, :username, :email, :address, :phone, :product_name, :quantity, :price, :status)");
        return $stmt->execute([
            ':name' => $name,
            ':username' => $username,
            ':email' => $email,
            ':address' => $address,
            ':phone' => $phone,
            ':product_name' => $productName,
            ':quantity' => $quantity,
            ':price' => $price,  // Đảm bảo có cột price
            ':status' => $status
        ]);
    }
    

    // Xóa đơn hàng
    public function deleteOrder($orderId)
    {
        $stmt = $this->pdo->prepare("DELETE FROM orders WHERE id = :order_id");
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        return $stmt->execute();
    }
    public function getTotalSalesReport($type)
    {
        $query = "";
    
        switch ($type) {
            case 'day':
                $query = "SELECT DATE(created_at) AS time_period, 
                                 SUM(price * quantity) AS total_sales 
                          FROM orders 
                          WHERE status = 'Đã giao'
                          GROUP BY DATE(created_at)";
                break;
            case 'month':
                $query = "SELECT CONCAT(YEAR(created_at), '-', LPAD(MONTH(created_at), 2, '0')) AS time_period, 
                                 SUM(price * quantity) AS total_sales 
                          FROM orders 
                          WHERE status = 'Đã giao'
                          GROUP BY YEAR(created_at), MONTH(created_at)";
                break;
            default:
                return [];
        }
    
        $stmt = $this->pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    
}
