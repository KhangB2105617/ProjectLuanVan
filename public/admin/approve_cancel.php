<?php
session_start();
require_once __DIR__ . '/../../src/bootstrap.php';

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Bạn không có quyền thực hiện thao tác này.";
    header("Location: /admin/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $approve = isset($_POST['approve']) ? (int)$_POST['approve'] : null;

    if ($orderId > 0 && ($approve === 0 || $approve === 1)) {
        $stmt = $PDO->prepare("UPDATE orders SET cancel_approved = :approve WHERE id = :id");
        $stmt->execute([
            'approve' => $approve,
            'id' => $orderId
        ]);

        $_SESSION['success'] = "Cập nhật trạng thái hủy đơn thành công.";
    } else {
        $_SESSION['error'] = "Dữ liệu không hợp lệ.";
    }
}

header("Location: /admin/manage_shipping.php");
exit;
