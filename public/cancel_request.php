<?php
session_start();
require_once __DIR__ . '/../src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = $_POST['order_id'] ?? null;
    $username = $_SESSION['username'] ?? null;

    if ($orderId && $username) {
        // Đảm bảo chỉ cập nhật đơn của chính người dùng đó
        $stmt = $PDO->prepare("UPDATE orders SET cancel_request = 1 WHERE id = :id AND username = :username");
        $stmt->execute([
            'id' => $orderId,
            'username' => $username
        ]);
    }
}

header('Location: /orders.php'); // hoặc tên file hiển thị đơn hàng
exit;