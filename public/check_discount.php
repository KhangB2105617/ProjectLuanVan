<?php
session_start();
require_once __DIR__ . '/../src/bootstrap.php';

use NL\User;
use NL\Product;

header('Content-Type: application/json');

if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Chưa đăng nhập']);
    exit;
}

$userId = $_SESSION['user_id'];
$discountCode = $_POST['discount_code'] ?? '';

if (!$discountCode) {
    echo json_encode(['error' => 'Không có mã giảm giá']);
    exit;
}

$userModel = new User($PDO);
$productModel = new Product($PDO);
$cartItemModel = new \NL\CartItem($PDO);

$cartItems = $cartItemModel->getByUser($userId);
if (empty($cartItems)) {
    echo json_encode(['error' => 'Giỏ hàng trống']);
    exit;
}

$totalPrice = 0;
foreach ($cartItems as $item) {
    $totalPrice += $item->price * $item->quantity;
}

$stmt = $PDO->prepare("
    SELECT d.*, s.id as saved_id
    FROM discount_codes d
    JOIN user_discount_codes s ON s.discount_code_id = d.id
    WHERE d.code = ? AND s.user_id = ? AND s.used = 0
        AND (d.expired_at IS NULL OR d.expired_at > NOW())
        AND d.used_count < d.max_usage
");
$stmt->execute([$discountCode, $userId]);
$discount = $stmt->fetch();

if (!$discount) {
    echo json_encode(['error' => 'Mã giảm giá không hợp lệ']);
    exit;
}

if ($discount['min_order_amount'] !== null && $totalPrice < $discount['min_order_amount']) {
    echo json_encode(['error' => 'Không đủ điều kiện sử dụng mã giảm giá']);
    exit;
}

$discountAmount = $discount['discount_type'] === 'percent'
    ? $totalPrice * ($discount['discount_value'] / 100)
    : $discount['discount_value'];

$discountAmount = min($discountAmount, $totalPrice);
$totalAfterDiscount = round($totalPrice - $discountAmount);

echo json_encode([
    'success' => true,
    'discountAmount' => $discountAmount,
    'totalAfterDiscount' => $totalAfterDiscount,
    'formattedDiscount' => number_format($discountAmount, 0, ',', '.') . ' VNĐ',
    'formattedTotal' => number_format($totalAfterDiscount, 0, ',', '.') . ' VNĐ'
]);
exit;