<?php
session_start();
require_once __DIR__ . '/../src/bootstrap.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Bạn cần đăng nhập để lưu mã giảm giá.";
    header('Location: /login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$discount_code_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($discount_code_id <= 0) {
    $_SESSION['error'] = "Mã không hợp lệ.";
    header('Location: /index.php');
    exit;
}

// Kiểm tra xem mã có tồn tại và còn hiệu lực không
$stmt = $PDO->prepare("
    SELECT * FROM discount_codes 
    WHERE id = ? 
    AND (expired_at IS NULL OR expired_at >= NOW())
");
$stmt->execute([$discount_code_id]);
$discount = $stmt->fetch();

if (!$discount) {
    $_SESSION['error'] = "Mã giảm giá không tồn tại hoặc đã hết hạn.";
    header('Location: /index.php');
    exit;
}

// Kiểm tra xem đã lưu chưa
$stmt = $PDO->prepare("
    SELECT id FROM user_discount_codes 
    WHERE user_id = ? AND discount_code_id = ?
");
$stmt->execute([$user_id, $discount_code_id]);

if ($stmt->fetch()) {
    $_SESSION['message'] = "Bạn đã lưu mã này rồi.";
    header('Location: /index.php');
    exit;
}

// Lưu mã
$stmt = $PDO->prepare("
    INSERT INTO user_discount_codes (user_id, discount_code_id)
    VALUES (?, ?)
");
$stmt->execute([$user_id, $discount_code_id]);

$_SESSION['success'] = "Lưu mã thành công!";
header('Location: /index.php');
exit;
