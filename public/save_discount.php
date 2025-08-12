<?php
session_start();
require_once __DIR__ . '/../src/bootstrap.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để lưu mã giảm giá.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$discount_code_id = isset($_POST['code_id']) ? (int)$_POST['code_id'] : 0;

if ($discount_code_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Mã không hợp lệ.']);
    exit;
}

// Kiểm tra mã còn hiệu lực
$stmt = $PDO->prepare("
    SELECT * FROM discount_codes 
    WHERE id = ? 
    AND (expired_at IS NULL OR expired_at >= NOW())
");
$stmt->execute([$discount_code_id]);
$discount = $stmt->fetch();

if (!$discount) {
    echo json_encode(['success' => false, 'message' => 'Mã giảm giá không tồn tại hoặc đã hết hạn.']);
    exit;
}

// Kiểm tra đã lưu chưa
$stmt = $PDO->prepare("
    SELECT id FROM user_discount_codes 
    WHERE user_id = ? AND discount_code_id = ?
");
$stmt->execute([$user_id, $discount_code_id]);

if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Bạn đã lưu mã này rồi.']);
    exit;
}

// Lưu mã
$stmt = $PDO->prepare("
    INSERT INTO user_discount_codes (user_id, discount_code_id)
    VALUES (?, ?)
");
$stmt->execute([$user_id, $discount_code_id]);

echo json_encode(['success' => true, 'message' => 'Lưu mã thành công!']);
exit;