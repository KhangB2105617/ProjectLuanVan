<?php
session_start();
require_once __DIR__ . '/../src/bootstrap.php';

$orderId = $_GET['id'] ?? null;
$username = $_SESSION['username'] ?? null;

if (!$orderId || !$username) {
    die('❌ Thiếu thông tin đơn hàng.');
}

$stmt = $PDO->prepare("
    SELECT o.id AS order_id, o.created_at AS order_date, o.status, o.total_price,
           o.discount_code, o.discount_amount, o.payment_method,
           oi.product_name, oi.quantity, oi.price,
           u.username, u.email, u.address, u.phone
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN users u ON o.username = u.username
    WHERE o.id = :order_id AND o.username = :username
");

$stmt->execute([
    'order_id' => $orderId,
    'username' => $username
]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    die('❌ Không tìm thấy đơn hàng.');
}

// Tách dữ liệu
$order = [
    'order_id' => $rows[0]['order_id'],
    'order_date' => $rows[0]['order_date'],
    'status' => $rows[0]['status'],
    'total_price' => $rows[0]['total_price'],
    'discount_code' => $rows[0]['discount_code'],
    'discount_amount' => $rows[0]['discount_amount'],
    'username' => $rows[0]['username'],
    'email' => $rows[0]['email'],
    'address' => $rows[0]['address'],
    'phone' => $rows[0]['phone'],
    'payment_method' => $rows[0]['payment_method'],
    'items' => []
];

foreach ($rows as $row) {
    $order['items'][] = [
        'product_name' => $row['product_name'],
        'quantity' => $row['quantity'],
        'price' => $row['price'],
    ];
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Hóa đơn #<?= $order['order_id'] ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 30px;
            color: #000;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left img {
            width: 120px;
        }

        .header-right {
            text-align: right;
        }

        .header-right h2 {
            margin: 0;
            font-size: 24px;
        }

        .info-section {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            margin-bottom: 20px;
        }

        .info-box {
            width: 48%;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
        }

        th {
            background-color: #f2f2f2;
        }

        .text-end {
            text-align: right;
        }

        .total {
            margin-top: 20px;
            font-size: 16px;
        }

        @media print {
            button {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="header-left">
            <img src="/assets/img/Logo/logo-ngang.png" alt="Logo cửa hàng">
        </div>
        <div class="header-right">
            <h2>HÓA ĐƠN BÁN HÀNG</h2>
            <p><strong>Mã hóa đơn:</strong> #<?= $order['order_id'] ?></p>
            <p><strong>Ngày đặt:</strong> <?= $order['order_date'] ?></p>
        </div>
    </div>

    <div class="info-section">
        <div class="info-box">
            <strong>🏪 CỬA HÀNG CLASSIC WATCH</strong><br>
            Địa chỉ: 123, Đ. 3 Tháng 2, Xuân Khánh, Ninh Kiều, Cần Thơ, Việt Nam<br>
            Email: classicwatch@gmail.com<br>
            SĐT: 0916337802
        </div>
        <div class="info-box">
            <strong>👤 KHÁCH HÀNG</strong><br>
            Họ tên: <?= htmlspecialchars($order['username']) ?><br>
            Địa chỉ: <?= htmlspecialchars($order['address']) ?><br>
            Email: <?= htmlspecialchars($order['email']) ?><br>
            SĐT: <?= htmlspecialchars($order['phone']) ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tên sản phẩm</th>
                <th>Số lượng</th>
                <th>Đơn giá</th>
                <th>Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order['items'] as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td><?= number_format($item['price'], 0, ',', '.') ?> VNĐ</td>
                    <td><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?> VNĐ</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total text-end">
        <?php if (!empty($order['discount_code'])): ?>
            <p><strong>Mã giảm giá:</strong> <?= $order['discount_code'] ?></p>
            <p><strong>Giảm giá:</strong> -<?= number_format($order['discount_amount'], 0, ',', '.') ?> VNĐ</p>
        <?php endif; ?>
        <p><strong>Tổng thanh toán:</strong> <?= number_format($order['total_price'], 0, ',', '.') ?> VNĐ</p>
        <p><strong>Phương thức thanh toán:</strong>
            <?php
            if ($order['payment_method'] === 'cod') {
                echo 'Thanh toán khi nhận hàng';
            } elseif ($order['payment_method'] === 'e_wallet') {
                echo 'Thanh toán bằng VNPay';
            } else {
                echo 'Không xác định';
            }
            ?>
        </p>
    </div>

    <div class="text-center" style="margin-top: 30px;">
        <button onclick="window.print()">🖨️ In hóa đơn</button>
        <a href="/orders.php" class="btn btn-secondary" style="margin-right: 10px;">🔙 Quay lại đơn hàng</a>
    </div>
</body>

</html>