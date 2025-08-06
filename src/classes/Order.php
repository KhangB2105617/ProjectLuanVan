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
            o.payment_method,
            o.created_at,
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

    public function getFilteredOrders($filters = [])
    {
        $query = "
        SELECT 
            o.id,
            o.customer_name,
            o.customer_email,
            o.customer_address,
            o.customer_phone,
            o.status,
            o.cancel_request,
            o.cancel_approved,
            o.payment_method,
            o.created_at,
            oi.quantity,
            oi.price,
            p.name AS product_name
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE 1
    ";

        $params = [];

        // Trạng thái
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'Đã hủy') {
                $query .= " AND o.cancel_approved = 1";
            } else {
                $query .= " AND o.status = :status AND (o.cancel_approved IS NULL OR o.cancel_approved = 0)";
                $params[':status'] = $filters['status'];
            }
        }

        // Phương thức thanh toán
        if (!empty($filters['payment_method'])) {
            $query .= " AND o.payment_method = :payment_method";
            $params[':payment_method'] = $filters['payment_method'];
        }

        // Ngày bắt đầu
        if (!empty($filters['from'])) {
            $query .= " AND DATE(o.created_at) >= :from";
            $params[':from'] = $filters['from'];
        }

        // Ngày kết thúc
        if (!empty($filters['to'])) {
            $query .= " AND DATE(o.created_at) <= :to";
            $params[':to'] = $filters['to'];
        }

        $query .= " ORDER BY o.created_at DESC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
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
   public function getTotalSalesReport($type = 'month', $from = null, $to = null)
{
    $condition = "";
    $params = [];

    if ($from && $to) {
        $condition = "AND created_at BETWEEN :from AND :to";
        $params[':from'] = $from;
        $params[':to'] = $to;
    }

    switch ($type) {
        case 'month':
            $sql = "
                SELECT MONTH(created_at) AS time_period, SUM(total_price) AS total_sales
                FROM orders
                WHERE status = 'Đã giao' $condition
                GROUP BY MONTH(created_at)
                ORDER BY MONTH(created_at)
            ";
            break;
        case 'day':
            $sql = "
                SELECT DATE(created_at) AS time_period, SUM(total_price) AS total_sales
                FROM orders
                WHERE status = 'Đã giao' $condition
                GROUP BY DATE(created_at)
                ORDER BY DATE(created_at)
            ";
            break;
        case 'year':
            $sql = "
                SELECT YEAR(created_at) AS time_period, SUM(total_price) AS total_sales
                FROM orders
                WHERE status = 'Đã giao' $condition
                GROUP BY YEAR(created_at)
                ORDER BY YEAR(created_at)
            ";
            break;
        case 'quarter':
            $sql = "
                SELECT QUARTER(created_at) AS time_period, SUM(total_price) AS total_sales
                FROM orders
                WHERE status = 'Đã giao' $condition
                GROUP BY QUARTER(created_at)
                ORDER BY QUARTER(created_at)
            ";
            break;
        default:
            return [];
    }

    $stmt = $this->pdo->prepare($sql);
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
    public function getTotalOrdersInMonth(int $month, int $year): int
{
    $stmt = $this->pdo->prepare("
        SELECT COUNT(*) 
        FROM orders 
        WHERE MONTH(created_at) = :month AND YEAR(created_at) = :year
    ");
    $stmt->execute([
        ':month' => $month,
        ':year' => $year
    ]);
    return (int) $stmt->fetchColumn();
}

public function getTotalProductsSoldInMonth(int $month, int $year): int
{
    $stmt = $this->pdo->prepare("
        SELECT SUM(oi.quantity) 
        FROM order_items oi
        INNER JOIN orders o ON oi.order_id = o.id
        WHERE MONTH(o.created_at) = :month AND YEAR(o.created_at) = :year
    ");
    $stmt->execute([
        ':month' => $month,
        ':year' => $year
    ]);
    return (int) $stmt->fetchColumn() ?? 0;
}
// Doanh thu mỗi ngày trong tháng
public function getRevenueByDay($month, $year): array {
    $stmt = $this->pdo->prepare("
        SELECT DATE(created_at) AS day, SUM(total_price - discount_amount) AS revenue
        FROM orders
        WHERE status = 'Đã giao' AND MONTH(created_at) = ? AND YEAR(created_at) = ?
        GROUP BY day
        ORDER BY day
    ");
    $stmt->execute([$month, $year]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Số lượng nhập mỗi ngày trong tháng
public function getImportQuantityByDay($month, $year)
{
    $sql = "
        SELECT DATE(change_date) as date, SUM(change_quantity) as total
        FROM stock_history
        WHERE change_type = 'in' 
          AND MONTH(change_date) = :month 
          AND YEAR(change_date) = :year
        GROUP BY DATE(change_date)
        ORDER BY DATE(change_date)
    ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['month' => $month, 'year' => $year]);
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}

// Số lượng xuất mỗi ngày trong tháng
public function getExportQuantityByDay($month, $year)
{
    $sql = "
        SELECT DATE(change_date) as date, SUM(change_quantity) as total
        FROM stock_history
        WHERE change_type = 'out' 
          AND MONTH(change_date) = :month 
          AND YEAR(change_date) = :year
        GROUP BY DATE(change_date)
        ORDER BY DATE(change_date)
    ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(['month' => $month, 'year' => $year]);
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}

// Lấy đơn hàng trong ngày cụ thể
public function getOrdersByDay($date): array {
    $stmt = $this->pdo->prepare("
        SELECT o.*, u.fullname, u.phone
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE DATE(o.created_at) = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Tổng doanh thu trong tháng
public function getTotalRevenueInMonth($month, $year): float {
    $stmt = $this->pdo->prepare("
        SELECT SUM(total_price - discount_amount) AS total
        FROM orders
        WHERE status = 'delivered' AND MONTH(created_at) = ? AND YEAR(created_at) = ?
    ");
    $stmt->execute([$month, $year]);
    return (float) $stmt->fetchColumn();
}

// Top sản phẩm bán chạy trong tháng
public function getTopSellingProductsByMonth($month, $year, $limit = 5): array {
    $stmt = $this->pdo->prepare("
        SELECT p.name, SUM(oi.quantity) AS total_sold
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN products p ON oi.product_id = p.id
        WHERE o.status = 'Đã giao' AND MONTH(o.created_at) = ? AND YEAR(o.created_at) = ?
        GROUP BY p.id
        ORDER BY total_sold DESC
        LIMIT ?
    ");
    $stmt->execute([$month, $year, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Top sản phẩm xuất kho nhiều nhất trong tháng
public function getTopExportedProductsByMonth($month, $year, $limit = 5): array {
    $stmt = $this->pdo->prepare("
        SELECT p.name, SUM(sh.quantity) AS total_exported
        FROM stock_history sh
        JOIN products p ON sh.product_id = p.id
        WHERE sh.type = 'export' AND MONTH(sh.created_at) = ? AND YEAR(sh.created_at) = ?
        GROUP BY sh.product_id
        ORDER BY total_exported DESC
        LIMIT ?
    ");
    $stmt->execute([$month, $year, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


}
