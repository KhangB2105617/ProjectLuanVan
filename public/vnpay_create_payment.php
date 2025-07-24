<?php
require_once __DIR__ . '/../src/bootstrap.php';
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Lấy order_id
$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($orderId <= 0) {
    die("❌ Mã đơn hàng không hợp lệ.");
}

// Lấy đơn hàng
$stmt = $PDO->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    die("❌ Không tìm thấy đơn hàng.");
}

// Nếu đã thanh toán
if (in_array($order['status'], ['đang xử lý', 'đang vận chuyển', 'đã giao'])) {
    die("✅ Đơn hàng đã được thanh toán.");
}

// Cấu hình VNPay
$vnp_TmnCode = "H9LD3WLN";
$vnp_HashSecret = "4IWS2CS492EC7RUJFEZ0TYKZE0UTHWZS";
$vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
$vnp_Returnurl = "http://shopwatch.localhost/vnpay_return.php";

// Dữ liệu giao dịch
$txnRef = $orderId . '_' . time();
$vnp_Amount = $order['total_price'] * 100;
$vnp_CreateDate = date('YmdHis');

$inputData = [
    "vnp_Version" => "2.1.0",
    "vnp_TmnCode" => $vnp_TmnCode,
    "vnp_Amount" => $vnp_Amount,
    "vnp_Command" => "pay",
    "vnp_CreateDate" => $vnp_CreateDate,
    "vnp_CurrCode" => "VND",
    "vnp_IpAddr" => $_SERVER['REMOTE_ADDR'],
    "vnp_Locale" => "vn",
    "vnp_OrderInfo" => "Thanh toan don hang #" . $orderId,
    "vnp_OrderType" => "billpayment",
    "vnp_ReturnUrl" => $vnp_Returnurl,
    "vnp_TxnRef" => $txnRef,
    "vnp_BankCode" => "NCB"
];

// Tạo chữ ký
ksort($inputData);
$query = [];
foreach ($inputData as $key => $value) {
    $query[] = urlencode($key) . "=" . urlencode($value);
}
$hashData = implode('&', $query);
$vnp_SecureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);
$inputData["vnp_SecureHash"] = $vnp_SecureHash;

// Lưu vào bảng vnpay_transactions
$stmt = $PDO->prepare("INSERT INTO vnpay_transactions (txn_ref, order_id, amount, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
$stmt->execute([$txnRef, $orderId, $order['total_price']]);

// Chuyển hướng đến VNPay
$vnp_Url .= '?' . http_build_query($inputData);
header('Location: ' . $vnp_Url);
exit;