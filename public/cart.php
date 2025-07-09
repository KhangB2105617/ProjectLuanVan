<?php
session_start();
include_once __DIR__ . '/../src/partials/header.php';
require_once __DIR__ . '/../src/bootstrap.php';

use NL\Product;

$productModel = new Product($PDO);

// Lấy giỏ hàng từ session
$cart = $_SESSION['cart'] ?? [];

// Nếu giỏ hàng trống, hiển thị thông báo
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
            <table class="table table-bordered">
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
                        <tr>
                            <td><img src="/assets/img/<?= htmlspecialchars($product->image); ?>" alt="<?= htmlspecialchars($product->name); ?>" style="width: 80px;"></td>
                            <td><?= htmlspecialchars($product->name); ?></td>
                            <td><?= number_format($product->price, 0, ',', '.'); ?> VNĐ</td>
                            <td>
                                <form method="post" action="update-cart.php">
                                    <input type="hidden" name="product_id" value="<?= $product->id; ?>">
                                    <input type="number" name="quantity" value="<?= $quantity; ?>" min="1" class="form-control" style="width: 80px;">
                                    <input type="hidden" name="action" value="update">
                                    <button type="submit" class="btn btn-sm btn-primary mt-2">Cập nhật</button>
                                </form>
                            </td>
                            <td><?= number_format($subtotal, 0, ',', '.'); ?> VNĐ</td>
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
                        <th colspan="2"><?= number_format($totalPrice, 0, ',', '.'); ?> VNĐ</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="d-flex justify-content-end">
            <a href="checkout.php" class="btn btn-success">Thanh toán</a>
        </div>
    </div>
</main>