<?php
session_start();
include_once __DIR__ . '/../src/partials/header.php';
require_once __DIR__ . '/../src/bootstrap.php';

use NL\Product;

$productModel = new Product($PDO);

// Lấy giỏ hàng từ session
$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    echo "<div class='container mt-5 text-center'><h2>Giỏ hàng của bạn đang trống</h2><a href='product.php' class='btn btn-primary mt-3'>Tiếp tục mua sắm</a></div>";
    exit;
}

// Lấy thông tin sản phẩm từ giỏ hàng
$productIds = array_keys($cart);
$products = $productModel->getProductsByIds($productIds);
$totalPrice = 0;
?>

<main>
    <div class="container mt-5">
        <h1 class="text-center">Giỏ Hàng</h1>
        <div class="table-responsive">
            <table class="table table-bordered align-middle text-center">
                <thead>
                    <tr>
                        <th>Hình ảnh</th>
                        <th>Tên sản phẩm</th>
                        <th>Giá</th>
                        <th>Số lượng</th>
                        <th>Thành tiền</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <?php
                        $quantity = $cart[$product->id];
                        $subtotal = $product->price * $quantity;
                        $totalPrice += $subtotal;
                        ?>
                        <tr data-id="<?= $product->id ?>">
                            <td><img src="/assets/img/<?= htmlspecialchars($product->image); ?>" alt="<?= htmlspecialchars($product->name); ?>" style="width: 80px;"></td>
                            <td><?= htmlspecialchars($product->name); ?></td>
                            <td><?= number_format($product->price, 0, ',', '.'); ?> VNĐ</td>
                            <td>
                                <input type="number" class="form-control quantity-input" value="<?= $quantity; ?>" min="1" style="width: 80px;" data-id="<?= $product->id ?>">
                            </td>
                            <td class="subtotal"><?= number_format($subtotal, 0, ',', '.'); ?> VNĐ</td>
                            <td>
                                <form method="post" action="update-cart.php">
                                    <input type="hidden" name="product_id" value="<?= $product->id; ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <button type="submit" class="btn btn-sm btn-danger">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end">Tổng cộng:</th>
                        <th colspan="2" id="total-price"><?= number_format($totalPrice, 0, ',', '.'); ?> VNĐ</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="d-flex justify-content-end">
            <a href="checkout.php" class="btn btn-success">Thanh toán</a>
        </div>
    </div>
</main>

<!-- ✅ Script tự động cập nhật số lượng -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const inputs = document.querySelectorAll('.quantity-input');

    inputs.forEach(input => {
        input.addEventListener('input', function () {
            const productId = this.dataset.id;
            let quantity = parseInt(this.value);

            if (isNaN(quantity) || quantity < 1) {
                this.value = 1; // Reset về 1
                Swal.fire({
                    icon: 'warning',
                    title: 'Số lượng không hợp lệ',
                    text: 'Số lượng phải lớn hơn 0',
                    timer: 2000,
                    showConfirmButton: false
                });
                quantity = 1;
            }

            fetch('update-cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'update',
                    product_id: productId,
                    quantity: quantity
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const row = input.closest('tr');
                    row.querySelector('.subtotal').textContent = data.subtotal_formatted + ' VNĐ';
                    document.getElementById('total-price').textContent = data.total_formatted + ' VNĐ';
                } else {
                    // Hiển thị lỗi từ server nếu có
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: data.message || 'Đã xảy ra lỗi không xác định'
                    });
                }
            });
        });
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>