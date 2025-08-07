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
        $condition = "AND o.created_at BETWEEN :from AND :to";
        $params[':from'] = $from;
        $params[':to'] = $to;
    }

    switch ($type) {
        case 'day':
            $groupBy = "DATE(o.created_at)";
            break;
        case 'month':
            $groupBy = "CAST(MONTH(o.created_at) AS UNSIGNED) AS time_period
";
            break;
        case 'year':
            $groupBy = "YEAR(o.created_at)";
            break;
        case 'quarter':
            $groupBy = "CONCAT(YEAR(o.created_at), '-Q', QUARTER(o.created_at))";
            break;
        default:
            return [];
    }

    $sql = "
        SELECT 
            $groupBy AS time_period,
            SUM(o.total_price) AS total_sales,
            COUNT(DISTINCT o.id) AS total_orders,
            SUM(oi.quantity) AS total_products
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.status = 'Đã giao' $condition
        GROUP BY time_period
        ORDER BY time_period
    ";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}
    public function getTopSellingProducts($limit = 5)
{
    $stmt = $this->pdo->prepare("
        SELECT 
            p.id, p.name, p.image, p.price, p.original_price, 
            SUM(oi.quantity) AS total_quantity
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN products p ON oi.product_id = p.id
        WHERE o.status = 'Đã giao'
        GROUP BY p.id
        ORDER BY total_quantity DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    public function getMonthlySalesReportByYear($year)
{
    // Tạo mảng kết quả với 12 tháng mặc định = object
    $results = [];
    for ($i = 1; $i <= 12; $i++) {
        $results[$i] = (object)[
            'month' => $i, // Thay 'time_period' bằng 'month' để khớp với SQL
            'total_sales' => 0.0,
            'total_orders' => 0,
            'total_products' => 0
        ];
    }

    // Lấy dữ liệu thực tế từ DB
    $stmt = $this->pdo->prepare("
        SELECT 
            MONTH(o.created_at) AS month,
            SUM(o.total_price) AS total_sales,
            COUNT(DISTINCT o.id) AS total_orders,
            SUM(oi.quantity) AS total_products
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.status = 'Đã giao'
        AND YEAR(o.created_at) = :year
        GROUP BY month
        ORDER BY month
    ");
    $stmt->execute([':year' => $year]);
    $data = $stmt->fetchAll(PDO::FETCH_OBJ);

    // Gán dữ liệu thực tế vào mảng
    foreach ($data as $item) {
        $month = (int)$item->month;
        $results[$month] = $item;
    }

    // Trả về mảng theo thứ tự từ 1–12
    return array_values($results);
}
public function getSalesReportByMonthYear($month, $year)
{
    $stmt = $this->pdo->prepare("
        SELECT 
            :month AS month,
            COALESCE(SUM(o.total_price), 0) AS total_sales,
            COUNT(DISTINCT o.id) AS total_orders,
            COALESCE(SUM(oi.quantity), 0) AS total_products
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.status = 'Đã giao'
        AND MONTH(o.created_at) = :month
        AND YEAR(o.created_at) = :year
    ");
    $stmt->execute([
        ':month' => $month,
        ':year' => $year
    ]);

    return $stmt->fetchAll(PDO::FETCH_OBJ);
}

}
