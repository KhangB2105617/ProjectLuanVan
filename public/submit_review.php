<?php
require_once __DIR__ . '/../src/bootstrap.php'; // Đảm bảo kết nối CSDL

// Kiểm tra dữ liệu từ form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_id = $_POST['product_id'] ?? 0;
    $customer_name = $_POST['customer_name'] ?? '';
    $rating = $_POST['rating'] ?? 5;
    $comment = $_POST['comment'] ?? '';

    if (!empty($customer_name) && $product_id > 0) {
        try {
            // Chuẩn bị câu lệnh SQL
            $stmt = $PDO->prepare("INSERT INTO reviews (product_id, customer_name, rating, comment, created_at) 
                                   VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$product_id, $customer_name, $rating, $comment]);

            // Chuyển hướng về trang sản phẩm
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
