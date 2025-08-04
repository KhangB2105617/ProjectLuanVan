<?php
session_start();
include_once __DIR__ . '/../src/partials/header.php';
require_once __DIR__ . '/../src/bootstrap.php';

use NL\Product;

$productModel = new Product($PDO);
$cart = [];

if (isset($_SESSION['user_id'])) {
    $stmt = $PDO->prepare("SELECT product_id, quantity FROM cart_items WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $cart[$item['product_id']] = $item['quantity'];
    }
} else {
    $cart = $_SESSION['cart'] ?? [];
}

if (empty($cart)) {
    echo "
    <div class='d-flex flex-column justify-content-center align-items-center text-center' style='min-height: 80vh;'>
        <img src='/assets/img/empty-cart.png' alt='Giỏ hàng trống' style='max-width: 300px;'>
        <h3 class='mt-2 fw-bold'>Giỏ hàng trống</h3>
        <p>Không có sản phẩm nào trong giỏ hàng</p>
        <a href='product.php' class='btn btn-primary mt-2 px-5 fw-bold'>Về trang mua sắm</a>
    </div>";
    exit;
}

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
                        $available = (int)$product->quantity;
                        $actualQty = min($quantity, $available);
                        $subtotal = $product->price * $actualQty;
                        $totalPrice += $subtotal;
                        ?>
                        <tr data-id="<?= $product->id ?>">
                            <td><img src="/assets/img/<?= htmlspecialchars($product->image); ?>" alt="<?= htmlspecialchars($product->name); ?>" style="width: 80px;"></td>
                            <td><?= htmlspecialchars($product->name); ?></td>
                            <td><?= number_format($product->price, 0, ',', '.'); ?> VNĐ</td>
                            <td>
                                <?php if ($available > 0): ?>
                                    <input
                                        type="number"
                                        class="form-control quantity-input"
                                        value="<?= $actualQty; ?>"
                                        min="1"
                                        max="<?= $available ?>"
                                        data-id="<?= $product->id ?>"
                                        data-max="<?= $available ?>"
                                        style="width: 80px;">
                                <?php else: ?>
                                    <span class="text-danger fw-bold">Hết hàng</span>
                                <?php endif; ?>
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const inputs = document.querySelectorAll('.quantity-input');

    inputs.forEach(input => {
        input.addEventListener('input', function() {
            const productId = this.dataset.id;
            const max = parseInt(this.dataset.max);
            let quantity = parseInt(this.value);

            if (!Number.isInteger(quantity) || isNaN(quantity) || quantity < 1) {
                quantity = 1;
                this.value = 1;
                Swal.fire({
                    icon: 'warning',
                    title: 'Số lượng không hợp lệ',
                    text: 'Số lượng phải từ 1 trở lên.',
                    timer: 2000,
                    showConfirmButton: false
                });
            }

            if (quantity > max) {
                quantity = max;
                this.value = max;
                Swal.fire({
                    icon: 'warning',
                    title: 'Vượt quá tồn kho',
                    text: `Chỉ còn ${max} sản phẩm trong kho.`,
                    timer: 2000,
                    showConfirmButton: false
                });
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
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: data.message || 'Đã xảy ra lỗi.'
                    });
                }
            });
        });
    });
});
</script>

<?php if (!empty($_SESSION['error'])): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Lỗi',
            text: <?= json_encode($_SESSION['error']) ?>,
            confirmButtonText: 'Đóng'
        });
    </script>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>