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
        $stmt = $this->pdo->query("
        SELECT 
            o.id,
            o.customer_name,
            o.customer_email,
            o.customer_address,
            o.customer_phone,
            o.status,
            o.cancel_request,
            o.cancel_approved,
            oi.quantity,
            oi.price,
            p.name AS product_name
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        ORDER BY o.id DESC
    ");
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getById($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateOrderStatus($orderId, $newStatus)
    {
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
    public function insertOrder($name, $username, $email, $address, $phone, $totalPrice, $status, $paymentMethod, $discountCode = null, $discountAmount = 0)
    {
        $stmt = $this->pdo->prepare("
        INSERT INTO orders (customer_name, username, customer_email, customer_address, customer_phone, total_price, status, payment_method, discount_code, discount_amount)
        VALUES (:name, :username, :email, :address, :phone, :total_price, :status, :payment_method, :discount_code, :discount_amount)
    ");
        $stmt->execute([
            ':name' => $name,
            ':username' => $username,
            ':email' => $email,
            ':address' => $address,
            ':phone' => $phone,
            ':total_price' => $totalPrice,
            ':status' => $status,
            ':payment_method' => $paymentMethod,
            ':discount_code' => $discountCode,
            ':discount_amount' => $discountAmount
        ]);

        return $this->pdo->lastInsertId(); // Trả về order_id vừa tạo
    }
    public function insertOrderItem($orderId, $productId, $productName, $quantity, $price)
    {
        $stmt = $this->pdo->prepare("
        INSERT INTO order_items (order_id, product_id, product_name, quantity, price)
        VALUES (:order_id, :product_id, :product_name, :quantity, :price)
    ");
        return $stmt->execute([
            ':order_id' => $orderId,
            ':product_id' => $productId,
            ':product_name' => $productName,
            ':quantity' => $quantity,
            ':price' => $price
        ]);
    }

    // Xóa đơn hàng
    public function deleteOrder($orderId)
    {
        $stmt = $this->pdo->prepare("DELETE FROM orders WHERE id = :order_id");
        $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
        return $stmt->execute();
    }
    public function getTotalSalesReport($type, $from = null, $to = null)
    {
        $params = [];
        $where = "WHERE o.status = 'Đã giao'";

        if ($from && $to) {
            $where .= " AND DATE(o.created_at) BETWEEN :from AND :to";
            $params[':from'] = $from;
            $params[':to'] = $to;
        }

        switch ($type) {
            case 'day':
                $query = "
                SELECT DATE(o.created_at) AS time_period, 
                       SUM(oi.price * oi.quantity) AS total_sales 
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                $where
                GROUP BY DATE(o.created_at)
                ORDER BY DATE(o.created_at)
            ";
                break;

            case 'month':
                $query = "
                SELECT CONCAT(YEAR(o.created_at), '-', LPAD(MONTH(o.created_at), 2, '0')) AS time_period, 
                       SUM(oi.price * oi.quantity) AS total_sales 
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                $where
                GROUP BY YEAR(o.created_at), MONTH(o.created_at)
                ORDER BY YEAR(o.created_at), MONTH(o.created_at)
            ";
                break;

            case 'quarter':
                $query = "
                SELECT CONCAT('Q', QUARTER(o.created_at), '-', YEAR(o.created_at)) AS time_period,
                       SUM(oi.price * oi.quantity) AS total_sales
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                $where
                GROUP BY YEAR(o.created_at), QUARTER(o.created_at)
                ORDER BY YEAR(o.created_at), QUARTER(o.created_at)
            ";
                break;

            case 'year':
                $query = "
                SELECT YEAR(o.created_at) AS time_period,
                       SUM(oi.price * oi.quantity) AS total_sales
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                $where
                GROUP BY YEAR(o.created_at)
                ORDER BY YEAR(o.created_at)
            ";
                break;

            default:
                return [];
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getTopSellingProducts($limit = 5)
    {
        $stmt = $this->pdo->prepare("
        SELECT oi.product_name, SUM(oi.quantity) AS total_quantity
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.status = 'Đã giao'
        GROUP BY oi.product_name
        ORDER BY total_quantity DESC
        LIMIT :limit
    ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    public function getLeastSellingProducts($limit = 5)
    {
        $stmt = $this->pdo->prepare("
        SELECT p.name AS product_name, 
               COALESCE(SUM(oi.quantity), 0) AS total_quantity
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'Đã giao'
        GROUP BY p.id, p.name
        ORDER BY total_quantity ASC
        LIMIT :limit
    ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    public function getOrderItems(int $orderId): array
    {
        $stmt = $this->pdo->prepare("
        SELECT 
            oi.product_id,
            oi.product_name,
            oi.quantity,
            oi.price,
            (oi.quantity * oi.price) AS subtotal
        FROM order_items oi
        WHERE oi.order_id = :order_id
    ");
        $stmt->execute([':order_id' => $orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function approveCancelRequest($orderId)
    {
        $stmt = $this->pdo->prepare("UPDATE orders SET cancel_approved = 1, status = 'Đã hủy' WHERE id = ?");
        return $stmt->execute([$orderId]);
    }

    public function denyCancelRequest($orderId)
    {
        $stmt = $this->pdo->prepare("UPDATE orders SET cancel_approved = 0 WHERE id = ?");
        return $stmt->execute([$orderId]);
    }

    public function create($userId, array $cartItems, float $totalPrice, string $paymentMethod = 'vnpay', string $status = 'đang xử lý')
{
    // Lấy thông tin người dùng từ bảng users
    $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new \Exception("Người dùng không tồn tại.");
    }

    // Tạo đơn hàng
    $stmt = $this->pdo->prepare("
        INSERT INTO orders (customer_name, username, customer_email, customer_address, customer_phone, total_price, status, payment_method)
        VALUES (:name, :username, :email, :address, :phone, :total, :status, :payment)
    ");
    $stmt->execute([
        ':name'     => $user['fullname'] ?? $user['username'],
        ':username' => $user['username'],
        ':email'    => $user['email'],
        ':address'  => $user['address'] ?? '',
        ':phone'    => $user['phone'] ?? '',
        ':total'    => $totalPrice,
        ':status'   => $status,
        ':payment'  => $paymentMethod
    ]);

    $orderId = $this->pdo->lastInsertId();

    // Chèn từng item vào order_items
    foreach ($cartItems as $item) {
        $this->insertOrderItem(
            $orderId,
            $item->product_id,
            $item->product_name,
            $item->quantity,
            $item->price
        );
    }

    return $orderId;
}

}
