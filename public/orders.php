<?php
session_start();
include_once __DIR__ . '/../src/partials/header.php'; 
require_once __DIR__ . '/../src/bootstrap.php';

// Lấy thông tin người dùng
$userId = $_SESSION['id'];

// Truy vấn các đơn hàng của người dùng
$query = $PDO->prepare("SELECT * FROM orders WHERE username = :username ORDER BY order_date DESC");
$query->execute(['username' => $_SESSION['username']]);
$orders = $query->fetchAll(PDO::FETCH_ASSOC);

?>

<main>
    <div class="container mt-5">
        <h2>Đơn hàng của tôi</h2>

        <?php if (count($orders) > 0): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Ngày đặt hàng</th>
                        <th>Tên sản phẩm</th>
                        <th>Số lượng</th>
                        <th>Trạng thái</th>
                        <th>Tổng giá</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['order_date']); ?></td>
                            <td><?= htmlspecialchars($order['product_name']); ?></td>
                            <td><?= htmlspecialchars($order['quantity']); ?></td>
                            <td><?= htmlspecialchars($order['status']); ?></td>
                            <td><?= number_format($order['price'], 0, ',', '.'); ?> VNĐ</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Chưa có đơn hàng nào.</p>
        <?php endif; ?>

    </div>
    </main>
