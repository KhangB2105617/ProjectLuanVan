<?php
session_start(); // Bắt đầu session

// Lấy các tham số từ POST
$action = $_POST['action'] ?? null;
$productId = $_POST['product_id'] ?? null;

if ($productId) {
    // Thực hiện hành động thêm sản phẩm vào giỏ
    switch ($action) {
        case 'add':
            // Thêm sản phẩm vào giỏ hàng
            $quantity = $_POST['quantity'] ?? 1;
            if (isset($_SESSION['cart'][$productId])) {
                // Nếu sản phẩm đã có trong giỏ thì cộng thêm số lượng
                $_SESSION['cart'][$productId] += $quantity;
            } else {
                // Nếu sản phẩm chưa có trong giỏ thì thêm mới
                $_SESSION['cart'][$productId] = $quantity;
            }
            echo "Sản phẩm đã được thêm vào giỏ!";
            break;

        case 'update':
            // Cập nhật số lượng sản phẩm trong giỏ hàng
            $quantity = $_POST['quantity'] ?? 1;
            if ($quantity > 0) {
                $_SESSION['cart'][$productId] = $quantity; // Cập nhật số lượng cho sản phẩm
            } else {
                unset($_SESSION['cart'][$productId]); // Nếu số lượng = 0, xóa sản phẩm khỏi giỏ
            }
            break;

        case 'remove':
            // Xóa sản phẩm khỏi giỏ hàng
            unset($_SESSION['cart'][$productId]);
            break;

        case 'clear':
            // Xóa toàn bộ giỏ hàng
            unset($_SESSION['cart']);
            break;

        default:
            // Nếu không có hành động hợp lệ
            echo "Hành động không hợp lệ!";
            break;
    }
} else {
    echo "Không có sản phẩm để xử lý!";
}

header("Location: cart.php"); // Chuyển hướng về trang giỏ hàng
exit;
?>
