<?php
require_once __DIR__ . '/../src/bootstrap.php';

use NL\Order;

// Lấy và kiểm tra order_id
$orderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
if (!$orderId) {
    echo "<h2>❌ Không tìm thấy đơn hàng!</h2>";
    exit;
}

$orderModel = new Order($PDO);
$order = $orderModel->getById($orderId);
$orderItems = $orderModel->getOrderItems($orderId);

if (!$order) {
    echo "<h2>❌ Đơn hàng không tồn tại!</h2>";
    exit;
}

include_once __DIR__ . '/../src/partials/header.php';
?>

<div class="container mt-5 mb-5">
    <h2 class="text-center text-success">🎉 Cảm ơn bạn đã đặt hàng tại Classic Watch!</h2>
    <p class="text-center">Mã đơn hàng của bạn là: <strong>#<?= htmlspecialchars($orderId) ?></strong></p>

    <?php if ($order['status'] !== 'Đang xử lý'): ?>
        <div class="alert alert-warning text-center">
            ⚠️ Đơn hàng của bạn hiện chưa được thanh toán.
            <br>
            <a href="/payment/vnpay_create.php?order_id=<?= $orderId ?>&total_price=<?= $order['total_price'] ?>"
                class="btn btn-primary mt-3">
                Thanh toán ngay
            </a>
        </div>
    <?php else: ?>
        <div class="alert alert-success text-center">
            ✅ Đơn hàng của bạn đã được thanh toán thành công. Cảm ơn bạn!
        </div>
    <?php endif; ?>

    <h4 class="mt-4">📦 Thông tin giao hàng</h4>
    <ul class="list-unstyled">
        <li><strong>Họ tên:</strong> <?= htmlspecialchars($order['customer_name']) ?></li>
        <li><strong>Email:</strong> <?= htmlspecialchars($order['customer_email']) ?></li>
        <li><strong>Địa chỉ:</strong> <?= htmlspecialchars($order['customer_address']) ?></li>
        <li><strong>Điện thoại:</strong> <?= htmlspecialchars($order['customer_phone']) ?></li>
        <li><strong>Phương thức thanh toán:</strong> <?= htmlspecialchars($order['payment_method'] ?? 'Chưa thanh toán') ?></li>
    </ul>

    <h4 class="mt-4">🧾 Chi tiết đơn hàng</h4>
    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th>Tên sản phẩm</th>
                <th class="text-center">Số lượng</th>
                <th class="text-end">Đơn giá</th>
                <th class="text-end">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            <?php $total = 0; ?>
            <?php foreach ($orderItems as $item): ?>
                <?php
                $subtotal = $item['quantity'] * $item['price'];
                $total += $subtotal;
                ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td class="text-center"><?= $item['quantity'] ?></td>
                    <td class="text-end"><?= number_format($item['price'], 0, ',', '.') ?> VNĐ</td>
                    <td class="text-end"><?= number_format($subtotal, 0, ',', '.') ?> VNĐ</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot class="table-light">
            <?php
            $shippingFee = 30000; // 30k phí ship
            ?>
            <tr>
                <th colspan="3" class="text-end">Tạm tính:</th>
                <th class="text-end"><?= number_format($total, 0, ',', '.') ?> VNĐ</th>
            </tr>
            <tr>
                <th colspan="3" class="text-end">Phí vận chuyển:</th>
                <th class="text-end"><?= number_format($shippingFee, 0, ',', '.') ?> VNĐ</th>
            </tr>

            <?php if (!empty($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                <tr>
                    <th colspan="3" class="text-end">Giảm giá:</th>
                    <th class="text-end">-<?= number_format($order['discount_amount'], 0, ',', '.') ?> VNĐ</th>
                </tr>
                <?php $total = $total + $shippingFee - $order['discount_amount']; ?>
            <?php else: ?>
                <?php $total = $total + $shippingFee; ?>
            <?php endif; ?>

            <tr>
                <th colspan="3" class="text-end">Tổng cộng:</th>
                <th class="text-end text-danger"><?= number_format($total, 0, ',', '.') ?> VNĐ</th>
            </tr>
        </tfoot>

    </table>
</div>

<?php include_once __DIR__ . '/../src/partials/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>