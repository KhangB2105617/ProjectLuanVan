<?php
session_start();
require_once __DIR__ . '/../src/bootstrap.php'; // Kết nối CSDL

// Kiểm tra đăng nhập
if (!isset($_SESSION['username'])) {
    die("Bạn cần đăng nhập để gửi đánh giá.");
}

// Kiểm tra dữ liệu từ form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = $_POST['product_id'] ?? 0;
    $product_name = $_POST['product_name'] ?? '';
    $customer_name = $_POST['customer_name'] ?? '';
    $rating = $_POST['rating'] ?? 5;
    $comment = $_POST['comment'] ?? '';
    $username = $_SESSION['username'];

    if (!empty($customer_name) && $product_id > 0 && !empty($product_name)) {
        try {
            // Kiểm tra xem người dùng đã mua sản phẩm này chưa (và đã giao)
            $checkStmt = $PDO->prepare("
                SELECT COUNT(*) FROM orders 
                WHERE username = ? AND product_name = ? AND status = 'Đã giao'
            ");
            $checkStmt->execute([$username, $product_name]);

            if ($checkStmt->fetchColumn() == 0) {
                die("Chỉ người đã mua và nhận sản phẩm mới được đánh giá.");
            }

            // Thêm đánh giá
            $stmt = $PDO->prepare("INSERT INTO reviews (product_id, customer_name, rating, comment, created_at) 
                                   VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$product_id, $customer_name, $rating, $comment]);

            // Chuyển hướng về trang chi tiết sản phẩm
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
