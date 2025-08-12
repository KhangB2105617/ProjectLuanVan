<?php
session_start();
require_once __DIR__ . '/../src/bootstrap.php';
date_default_timezone_set('Asia/Ho_Chi_Minh');

$vnp_HashSecret = "4IWS2CS492EC7RUJFEZ0TYKZE0UTHWZS";
$vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';

// Lấy dữ liệu để xác minh chữ ký
$inputData = [];
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}
unset($inputData['vnp_SecureHash']);

ksort($inputData);
$query = [];
foreach ($inputData as $key => $value) {
    $query[] = urlencode($key) . "=" . urlencode($value);
}
$hashData = implode('&', $query);
$secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

if ($secureHash !== $vnp_SecureHash) {
    exit("❌ Xác minh chữ ký thất bại!");
}

// Phân tích dữ liệu
$txnRef = $_GET['vnp_TxnRef'] ?? '';
$responseCode = $_GET['vnp_ResponseCode'] ?? '';
$amount = (int)$_GET['vnp_Amount'] / 100;

$parts = explode('_', $txnRef);
$orderId = (int)($parts[0] ?? 0);

// Kiểm tra đơn hàng
$stmt = $PDO->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

$stmt = $PDO->prepare("SELECT * FROM vnpay_transactions WHERE txn_ref = ?");
$stmt->execute([$txnRef]);
$txn = $stmt->fetch();

if (!$txn || !$order) {
    exit("❌ Giao dịch hoặc đơn hàng không tồn tại.");
}

// Kiểm tra timeout
$createdAt = strtotime($txn['created_at']);
if (time() - $createdAt > 1800) {
    exit("<h2>❌ Giao dịch đã quá thời gian cho phép.</h2>");
}

// Kết quả giao dịch
if ($responseCode === '00') {
    // Cập nhật trạng thái giao dịch
    $stmt = $PDO->prepare("UPDATE vnpay_transactions SET status = 'success', response_code = ?, message = ? WHERE txn_ref = ?");
    $stmt->execute([$responseCode, $_GET['vnp_Message'] ?? '', $txnRef]);

    // Cập nhật đơn hàng
    $stmt = $PDO->prepare("UPDATE orders SET status = 'chờ xử lý' WHERE id = ?");
    $stmt->execute([$orderId]);
    // XÓA GIỎ HÀNG
    if (isset($_SESSION['cart'])) {
        unset($_SESSION['cart']);
    }
    // Xóa giỏ hàng trong DB cho user hiện tại
    $userId = $_SESSION['user_id'] ?? null;
    if ($userId) {
        $stmt = $PDO->prepare("DELETE FROM cart_items WHERE user_id = ?");
        $stmt->execute([$userId]);
    }
    header("Location: order_success.php?order_id=$orderId");
    exit;
} else {
    if (isset($_SESSION['cart'])) {
        unset($_SESSION['cart']);
    }
    header("Location: order_success.php?order_id=$orderId");
    echo "<h2>❌ Thanh toán thất bại: " . htmlspecialchars($_GET['vnp_Message'] ?? '') . "</h2>";
}
