<?php
session_start();
require_once __DIR__ . '/../src/bootstrap.php';

use NL\User;
use NL\Product;
use NL\Order;
use NL\Stock;

// --- Kiểm tra đăng nhập ---
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'];
$userModel = new User($PDO);
$productModel = new Product($PDO);
$orderModel = new Order($PDO);
$stockModel = new Stock($PDO);
$user = $userModel->getByUsername($username);

// --- Lấy giỏ hàng ---
$cart = [];
$products = [];
$totalPrice = 0;

if (isset($_SESSION['user_id'])) {
    $cartItemModel = new \NL\CartItem($PDO);
    $cartItems = $cartItemModel->getByUser($_SESSION['user_id']);

    if (empty($cartItems)) {
        echo "<div class='container mt-5 text-center'><h2>Giỏ hàng của bạn đang trống</h2><a href='product.php' class='btn btn-primary mt-3'>Tiếp tục mua sắm</a></div>";
        exit;
    }

    // Build $cart and $products from database
    foreach ($cartItems as $item) {
        $cart[$item->product_id] = $item->quantity;
        $products[] = (object)[
            'id' => $item->product_id,
            'name' => $item->name,
            'price' => $item->price,
            'image' => $item->image,
            'quantity' => $item->quantity // assuming stock left
        ];
    }
} else {
    // Người chưa đăng nhập: dùng session
    $cart = $_SESSION['cart'] ?? [];

    if (empty($cart)) {
        echo "<div class='container mt-5 text-center'><h2>Giỏ hàng của bạn đang trống</h2><a href='product.php' class='btn btn-primary mt-3'>Tiếp tục mua sắm</a></div>";
        exit;
    }

    $productIds = array_keys($cart);
    $products = $productModel->getProductsByIds($productIds);
}

// --- Tính tổng và kiểm tra tồn kho ---
$productIds = array_keys($cart);
$products = $productModel->getProductsByIds($productIds);
$totalPrice = 0;
$insufficientItems = [];

foreach ($products as $product) {
    $quantity = $cart[$product->id];
    $totalPrice += $product->price * $quantity;
    if ($product->quantity < $quantity) {
        $insufficientItems[] = $product->name;
    }
}

if (!empty($insufficientItems)) {
    echo "<div class='container mt-5 text-center'><h2>Không đủ sản phẩm trong kho:</h2><ul>";
    foreach ($insufficientItems as $item) echo "<li>$item</li>";
    echo "</ul><a href='product.php' class='btn btn-primary mt-3'>Tiếp tục mua sắm</a></div>";
    exit;
}

// --- Mã giảm giá ---
$availableDiscounts = $PDO->prepare("
    SELECT d.code, d.discount_type, d.discount_value, d.expired_at
    FROM discount_codes d
    JOIN user_discount_codes s ON s.discount_code_id = d.id
    WHERE s.user_id = ? AND s.used = 0
        AND (d.expired_at IS NULL OR d.expired_at > NOW())
        AND d.used_count < d.max_usage
");
$availableDiscounts->execute([$user['id']]);
$availableDiscounts = $availableDiscounts->fetchAll(PDO::FETCH_ASSOC);


// --- Xử lý khi người dùng đặt hàng ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $address = htmlspecialchars($_POST['address']);
    $phone = htmlspecialchars($_POST['phone']);
    $paymentMethod = $_POST['payment_method'];
    $discountCode = $_POST['discount_code'] ?? null;

    // --- Áp dụng mã giảm giá ---
    $discountAmount = 0;
    $totalAfterDiscount = $totalPrice;

    if ($discountCode) {
        $stmt = $PDO->prepare("
    SELECT d.*, s.id as saved_id
    FROM discount_codes d
    JOIN user_discount_codes s ON s.discount_code_id = d.id
    WHERE d.code = ? AND s.user_id = ? AND s.used = 0
        AND (d.expired_at IS NULL OR d.expired_at > NOW())
        AND d.used_count < d.max_usage
");
$stmt->execute([$discountCode, $user['id']]);
$discount = $stmt->fetch();


        if ($discount) {
            $discountAmount = $discount['discount_type'] === 'percent'
                ? $totalPrice * ($discount['discount_value'] / 100)
                : $discount['discount_value'];
            $discountAmount = min($discountAmount, $totalPrice);
            $totalAfterDiscount = round($totalPrice - $discountAmount);
        } else {
            $discountCode = null;
        }
    }

    // --- Tạo đơn hàng ---
    $orderId = $orderModel->insertOrder(
        $name,
        $username,
        $email,
        $address,
        $phone,
        $totalAfterDiscount,
        'đang xử lý',
        $paymentMethod,
        $discountCode,
        round($discountAmount)
    );

    // --- Ghi nhận mã giảm giá ---
    if ($discountCode) {
        $PDO->prepare("UPDATE discount_codes SET used_count = used_count + 1 WHERE code = ?")->execute([$discountCode]);
        $PDO->prepare("UPDATE user_discount_codes SET used = 1, used_at = NOW() WHERE id = ?")->execute([$discount['saved_id']]);
    }

    // --- Lưu sản phẩm và cập nhật tồn kho ---
    foreach ($products as $product) {
        $qty = $cart[$product->id];
        $orderModel->insertOrderItem($orderId, $product->id, $product->name, $qty, $product->price);
        $stockModel->updateStockQuantity($product->id, $qty, 'out', null, null, null, false);
    }

    // --- Nếu dùng VNPay ---
    if ($paymentMethod === 'e_wallet') {
        $_SESSION['orderID'] = $orderId;
        $redirectUrl = "vnpay_create_payment.php?order_id=$orderId&total_price=$totalAfterDiscount";
        header("Location: $redirectUrl");
        exit;
    }

    // --- Thanh toán COD hoặc khác ---
    $cartItemModel->clear($user['id']);
    unset($_SESSION['cart']);
    header("Location: order_success.php?order_id=$orderId");
    exit;
}

include_once __DIR__ . '/../src/partials/header.php';
?>

<!-- HTML GIAO DIỆN BẮT ĐẦU -->
<main class="checkout-page">
    <div class="container mt-5">
        <h2 class="text-center mb-4">Chi tiết đơn hàng</h2>
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Tên</th>
                    <th class="text-end">Giá</th>
                    <th class="text-center">SL</th>
                    <th class="text-end">Tổng</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product):
                    $quantity = $cart[$product->id];
                    $subtotal = $product->price * $quantity;
                ?>
                    <tr>
                        <td style="width: 80px;">
                            <img src="/assets/img/<?= htmlspecialchars($product->image ?? 'no-image.jpg') ?>" class="img-fluid rounded" style="width: 70px;">
                        </td>
                        <td><?= htmlspecialchars($product->name) ?></td>
                        <td class="text-end"><?= number_format($product->price, 0, ',', '.') ?> VNĐ</td>
                        <td class="text-center"><?= $quantity ?></td>
                        <td class="text-end"><?= number_format($subtotal, 0, ',', '.') ?> VNĐ</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-end">Tổng cộng:</th>
                    <th class="text-end"><?= number_format($totalPrice, 0, ',', '.') ?> VNĐ</th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="container mt-4">
        <h3 class="text-center">Thông tin thanh toán</h3>
        <form method="post" class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">Họ tên</label>
                <input type="text" class="form-control" name="name" required value="<?= htmlspecialchars($user['username'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" name="email" required value="<?= htmlspecialchars($user['email'] ?? '') ?>">
            </div>
            <div class="col-12">
                <label for="address" class="form-label">Địa chỉ</label>
                <input type="text" class="form-control" name="address" required value="<?= htmlspecialchars($user['address'] ?? '') ?>">
            </div>
            <div class="col-12">
                <label for="phone" class="form-label">Số điện thoại</label>
                <input type="text" class="form-control" name="phone" required value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label for="discount_code" class="form-label">Mã giảm giá</label>
                <select class="form-select" name="discount_code">
                    <option value="">-- Chọn mã giảm giá --</option>
                    <?php foreach ($availableDiscounts as $d):
                        $label = $d['code'] . ' - ';
                        $label .= $d['discount_type'] === 'percent'
                            ? $d['discount_value'] . '%'
                            : number_format($d['discount_value'], 0, ',', '.') . ' VNĐ';
                        if ($d['expired_at']) {
                            $label .= ' (HSD: ' . date('d/m/Y H:i', strtotime($d['expired_at'])) . ')';
                        }
                    ?>
                        <option value="<?= htmlspecialchars($d['code']) ?>"><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="payment_method" class="form-label">Phương thức thanh toán</label>
                <select class="form-select" name="payment_method" required>
                    <option value="cod">Thanh toán khi nhận hàng (COD)</option>
                    <option value="credit_card">Thẻ tín dụng</option>
                    <option value="e_wallet">VNPay</option>
                </select>
            </div>
            <div class="col-12 text-center">
                <button type="submit" class="btn btn-success mt-3">Thanh toán</button>
            </div>
        </form>
    </div>
</main>

<?php include_once __DIR__ . '/../src/partials/footer.php'; ?>