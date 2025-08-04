<?php
session_start();
require_once __DIR__ . '/../src/bootstrap.php'; // Kết nối CSDL

if (!isset($_SESSION['username'])) {
    die("Bạn cần đăng nhập để gửi đánh giá.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = $_POST['product_id'] ?? 0;
    $customer_name = $_POST['customer_name'] ?? '';
    $rating = $_POST['rating'] ?? 5;
    $comment = $_POST['comment'] ?? '';
    $username = $_SESSION['username'];

    if (!empty($customer_name) && $product_id > 0) {
        try {
            // Kiểm tra người dùng đã mua sản phẩm và đã giao chưa
            $checkStmt = $PDO->prepare("
                SELECT COUNT(*) FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                WHERE o.username = ? AND oi.product_id = ? AND o.status = 'Đã giao'
            ");
            $checkStmt->execute([$username, $product_id]);

            if ($checkStmt->fetchColumn() == 0) {
                die("Chỉ người đã mua và nhận sản phẩm mới được đánh giá.");
            }

            // Thêm đánh giá
            $stmt = $PDO->prepare("INSERT INTO reviews (product_id, customer_name, rating, comment, created_at) 
                                   VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$product_id, $customer_name, $rating, $comment]);

            header("Location: product-details.php?id=" . $product_id);
            exit;
        } catch (PDOException $e) {
            die("Lỗi khi thêm đánh giá: " . $e->getMessage());
        }
    } else {
        echo "Vui lòng nhập đầy đủ thông tin.";
    }
} else {
    echo "Truy cập không hợp lệ.";
}
?>