<?php 
session_start();
include_once __DIR__ . '/../src/partials/header.php';
require_once __DIR__ . '/../src/bootstrap.php';

use NL\Product;
use NL\Order;
use NL\Stock;  // Import class Stock

$productModel = new Product($PDO);
$orderModel = new Order($PDO);
$stockModel = new Stock($PDO);  // Khởi tạo đối tượng Stock

// Lấy giỏ hàng từ session
$cart = $_SESSION['cart'] ?? [];
$username = $_SESSION['username']; // Đảm bảo rằng người dùng đã đăng nhập
// Nếu giỏ hàng trống, chuyển đến giỏ hàng
if (empty($cart)) {
    echo "<div class='container mt-5 text-center'><h2>Giỏ hàng của bạn đang trống</h2><a href='product.php' class='btn btn-primary mt-3'>Tiếp tục mua sắm</a></div>";
    exit;
}

// Lấy thông tin sản phẩm từ giỏ hàng
$productIds = array_keys($cart);
$products = $productModel->getProductsByIds($productIds);
$totalPrice = 0;
$insufficientStock = false; // Biến kiểm tra nếu có sản phẩm thiếu kho

// Tính tổng giá trị giỏ hàng và kiểm tra kho
foreach ($products as $product) {
    $quantity = $cart[$product->id];
    $totalPrice += $product->price * $quantity;
    
    // Kiểm tra số lượng sản phẩm trong kho
    if ($product->quantity < $quantity) {
        $insufficientStock = true; // Đánh dấu nếu có sản phẩm thiếu kho
    }
}

if ($insufficientStock) {
    echo "<div class='container mt-5 text-center'><h2>Không đủ sản phẩm trong kho để hoàn tất đơn hàng</h2><a href='product.php' class='btn btn-primary mt-3'>Tiếp tục mua sắm</a></div>";
    exit; // Dừng quá trình thanh toán nếu thiếu kho
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy thông tin từ form thanh toán
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $address = htmlspecialchars($_POST['address']);
    $phone = htmlspecialchars($_POST['phone']);

    // Lưu đơn hàng vào cơ sở dữ liệu
    foreach ($products as $product) {
        $quantity = $cart[$product->id];
        $subtotal = $product->price * $quantity;

        // Thêm đơn hàng vào database
        $orderModel->createOrder($name, $username,$email, $address, $phone, $product->name, $quantity, $subtotal, 'Đang xử lý');

        // Cập nhật số lượng sản phẩm trong kho
        $stockModel->updateStockQuantity($product->id, $quantity, 'out'); // Trừ số lượng khi xuất kho
    }

    // Xóa giỏ hàng sau khi thanh toán
    unset($_SESSION['cart']);

    echo "<div class='container mt-5 text-center'>
            <h2>Cảm ơn bạn đã thanh toán!</h2>
            <p>Đơn hàng của bạn đã được xử lý thành công.</p>
            <a href='product.php' class='btn btn-primary mt-3'>Tiếp tục mua sắm</a>
          </div>";
    exit;
}
?>

<main class="checkout-page">
<main>
    <div class="container mt-5">
        <h1 class="text-center">Thông tin thanh toán</h1>
        <form method="post">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="name">Họ và tên</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label for="address">Địa chỉ</label>
                <input type="text" id="address" name="address" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="phone">Số điện thoại</label>
                <input type="text" id="phone" name="phone" class="form-control" required>
            </div>

            <!-- Chọn phương thức thanh toán -->
            <div class="form-group">
                <label for="payment-method">Phương thức thanh toán</label>
                <select id="payment-method" name="payment_method" class="form-control" required>
                    <option value="credit_card">Thanh toán qua thẻ tín dụng</option>
                    <option value="cod">Thanh toán khi nhận hàng</option>
                    <option value="e_wallet">Thanh toán qua ví điện tử</option>
                </select>
            </div>

            <div class="mt-4">
                <h4>Tổng cộng: <?= number_format($totalPrice, 0, ',', '.'); ?> VNĐ</h4>
            </div>
            <button type="submit" class="btn btn-success mt-3">Thanh toán</button>
        </form>
    </div>
</main>

</main>

<?php include_once __DIR__ . '/../src/partials/footer.php'; ?>
