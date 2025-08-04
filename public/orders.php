<?php
session_start();
include_once __DIR__ . '/../src/partials/header.php';
require_once __DIR__ . '/../src/bootstrap.php';

// Lấy thông tin người dùng
$userId = $_SESSION['id'];

// Truy vấn các đơn hàng của người dùng
$query = $PDO->prepare("
    SELECT o.id AS order_id, o.created_at AS order_date, o.status, o.total_price,
           o.discount_code, o.discount_amount,
           o.cancel_request, o.cancel_approved,
           oi.product_id, oi.product_name, oi.quantity, oi.price,
           p.image
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.username = :username
    ORDER BY o.created_at DESC
");

$query->execute(['username' => $_SESSION['username']]);
$rawOrders = $query->fetchAll(PDO::FETCH_ASSOC);

$orders = [];

foreach ($rawOrders as $row) {
    $orderId = $row['order_id'];

    if (!isset($orders[$orderId])) {
        $orders[$orderId] = [
            'order_date' => $row['order_date'],
            'status' => $row['status'],
            'total_price' => $row['total_price'],
            'discount_code' => $row['discount_code'] ?? null,
            'discount_amount' => $row['discount_amount'] ?? 0,
            'cancel_request' => $row['cancel_request'],
            'cancel_approved' => $row['cancel_approved'],
            'items' => []
        ];
    }

    $orders[$orderId]['items'][] = [
        'product_name' => $row['product_name'],
        'quantity' => $row['quantity'],
        'price' => $row['price'],
        'image' => $row['image']
    ];
}

?>

<main>
    <div class="container mt-5">
        <h2>Đơn hàng của tôi</h2>

        <?php if (count($orders) > 0): ?>
            <?php foreach ($orders as $orderId => $order): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <strong>Đơn hàng #<?= $orderId; ?></strong> |
                        Ngày đặt: <?= htmlspecialchars($order['order_date']); ?> |
                        Trạng thái: <?= htmlspecialchars($order['status']); ?>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Hình ảnh</th>
                                    <th>Tên sản phẩm</th>
                                    <th>Số lượng</th>
                                    <th>Đơn giá</th>
                                    <th>Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order['items'] as $item): ?>
                                    <tr>
                                        <td>
                                            <img src="/assets/img/<?= htmlspecialchars($item['image']); ?>" alt="Ảnh sản phẩm" style="width: 60px; height: auto;">
                                        </td>
                                        <td><?= htmlspecialchars($item['product_name']); ?></td>
                                        <td><?= $item['quantity']; ?></td>
                                        <td><?= number_format($item['price'], 0, ',', '.'); ?> VNĐ</td>
                                        <td><?= number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?> VNĐ</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="text-end">
                            <?php if (!empty($order['discount_code'])): ?>
                                <div><strong>Mã giảm giá:</strong> <?= htmlspecialchars($order['discount_code']); ?></div>
                                <div><strong>Giảm giá:</strong> -<?= number_format($order['discount_amount'], 0, ',', '.'); ?> VNĐ</div>
                            <?php endif; ?>
                            <div><strong>Tổng cộng:</strong> <?= number_format($order['total_price'] - $order['discount_amount'], 0, ',', '.'); ?> VNĐ</div>
                            <?php if ($order['status'] === 'Đang xử lý' && !$order['cancel_request']): ?>
                                <form method="post" action="/cancel_request.php">
                                    <input type="hidden" name="order_id" value="<?= $orderId; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm mt-2">Yêu cầu hủy đơn</button>
                                </form>
                            <?php elseif ($order['cancel_request'] && is_null($order['cancel_approved'])): ?>
                                <p class="text-warning mt-2">Đã gửi yêu cầu hủy, đang chờ duyệt...</p>
                            <?php elseif ($order['cancel_approved'] === 1): ?>
                                <p class="text-success mt-2">Đơn hàng đã được hủy.</p>
                            <?php elseif ($order['cancel_approved'] === 0): ?>
                                <p class="text-danger mt-2">Yêu cầu hủy đã bị từ chối.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>
        <?php else: ?>
            <p>Chưa có đơn hàng nào.</p>
        <?php endif; ?>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>