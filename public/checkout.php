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

// --- Phí vận chuyển ---
$shippingFee = 30000;
$totalPriceWithShipping = $totalPrice + $shippingFee;

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
$discountError = null; // Khởi tạo mặc định để không lỗi khi render lại form

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
    $totalAfterDiscount = $totalPriceWithShipping;

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

        $discountError = null;

        if ($discount) {
            if ($discount['min_order_amount'] !== null && $totalPrice < $discount['min_order_amount']) {
                $discountError = "Đơn hàng tối thiểu cần đạt " . number_format($discount['min_order_amount'], 0, ',', '.') . " VNĐ để sử dụng mã này.";
            } else {
                $discountAmount = $discount['discount_type'] === 'percent'
                    ? $totalPrice * ($discount['discount_value'] / 100)
                    : $discount['discount_value'];
                $discountAmount = min($discountAmount, $totalPrice);
                $totalAfterDiscount = round($totalPriceWithShipping - $discountAmount);
            }
        } else {
            $discountError = "Mã giảm giá không hợp lệ hoặc đã được sử dụng.";
        }
    }

    // --- Tạo đơn hàng ---
    if (!$discountError) {
        // --- Tạo đơn hàng ---
        $orderId = $orderModel->insertOrder(
            $name,
            $username,
            $email,
            $address,
            $phone,
            $totalAfterDiscount,
            'chờ xử lý',
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
}
$old = [
    'name' => $_POST['name'] ?? '',
    'email' => $_POST['email'] ?? '',
    'address' => $_POST['address'] ?? '',
    'phone' => $_POST['phone'] ?? '',
    'payment_method' => $_POST['payment_method'] ?? '',
    'discount_code' => $_POST['discount_code'] ?? '',
];
include_once __DIR__ . '/../src/partials/header.php';
?>
<style>
    .checkout-page {
        background-color: #f8f9fa;
        padding-top: 40px;
        padding-bottom: 40px;
    }

    .checkout-page .container {
        max-width: 1000px;
        background: white;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
    }

    .checkout-page h3 {
        font-weight: bold;
        margin-bottom: 30px;
    }

    .table th,
    .table td {
        vertical-align: middle !important;
    }

    .table img {
        border: 1px solid #dee2e6;
        border-radius: 8px;
    }

    .btn-success {
        padding: 12px 30px;
        font-size: 18px;
        border-radius: 8px;
    }

    .form-label {
        font-weight: 600;
    }

    .alert-warning {
        font-size: 14px;
    }
</style>

<!-- HTML GIAO DIỆN BẮT ĐẦU -->
<main class="checkout-page">

    <div class="container mt-4">
        <h3 class="text-center">Thông tin thanh toán</h3>
        <?php $discountCode = $discountCode ?? ''; ?>
        <form method="post" class="row g-4 bg-white rounded">
            <!-- CHI TIẾT GIỎ HÀNG ĐƯA VÀO TRONG FORM -->
            <div class="col-12">
                <h4>Chi tiết sản phẩm</h4>
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Tên</th>
                            <th class="text-end">Giá</th>
                            <th class="text-center">Số lượng</th>
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
                            <th colspan="4" class="text-end">Tạm tính:</th>
                            <th class="text-end"><?= number_format($totalPrice, 0, ',', '.') ?> VNĐ</th>
                        </tr>
                        <tr>
                            <th colspan="4" class="text-end">Phí vận chuyển:</th>
                            <th class="text-end"><?= number_format($shippingFee, 0, ',', '.') ?> VNĐ</th>
                        </tr>
                        <?php if (!empty($discountCode) && !$discountError): ?>
                            <tr>
                                <th colspan="4" class="text-end">Giảm giá (<?= htmlspecialchars($discountCode) ?>):</th>
                                <th class="text-end text-danger">- <?= number_format($discountAmount, 0, ',', '.') ?> VNĐ</th>
                            </tr>
                            <tr>
                                <th colspan="4" class="text-end">Tổng cộng sau giảm:</th>
                                <th class="text-end text-success"><?= number_format($totalAfterDiscount, 0, ',', '.') ?> VNĐ</th>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <th colspan="4" class="text-end">Tổng cộng:</th>
                                <th class="text-end"><?= number_format($totalPriceWithShipping, 0, ',', '.') ?> VNĐ</th>
                            </tr>
                        <?php endif; ?>
                    </tfoot>
                </table>
            </div>
            <div class="col-md-6">
                <label for="name" class="form-label">Họ tên</label>
                <input type="text" class="form-control" name="name" required value="<?= htmlspecialchars($old['name'] ?: ($user['username'] ?? '')) ?>">
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" name="email" required value="<?= htmlspecialchars($old['email'] ?: ($user['email'] ?? '')) ?>">
            </div>
            <div class="col-12">
                <label for="address" class="form-label">Địa chỉ</label>
                <input type="text" class="form-control" name="address" required value="<?= htmlspecialchars($old['address'] ?: ($user['address'] ?? '')) ?>">
            </div>
            <div class="col-12">
                <label for="phone" class="form-label">Số điện thoại</label>
                <input type="text" class="form-control" name="phone" required value="<?= htmlspecialchars($old['phone'] ?: ($user['phone'] ?? '')) ?>">
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
                        <?php
                        $disabled = false;
                        if (isset($d['min_order_amount']) && $totalPrice < $d['min_order_amount']) {
                            $disabled = true;
                        }
                        ?>
                        <option value="<?= htmlspecialchars($d['code']) ?>"
                            <?= ($discountCode === $d['code']) ? 'selected' : '' ?>
                            <?= $disabled ? 'disabled' : '' ?>>
                            <?= htmlspecialchars($label) ?><?= $disabled ? ' (Không đủ điều kiện)' : '' ?>
                        </option>

                    <?php endforeach; ?>
                </select>
                <?php if (!empty($discountError)): ?>
                    <div class="alert alert-warning mt-2"><?= $discountError ?></div>
                <?php endif; ?>
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
<script>
    document.querySelector('select[name="discount_code"]').addEventListener('change', function() {
        const code = this.value;
        const formData = new FormData();
        formData.append('discount_code', code);

        fetch('check_discount.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                const tbody = document.querySelector('tfoot');
                if (data.error) {
                    tbody.innerHTML = `
                <tr><th colspan="4" class="text-end">Tổng cộng:</th><th class="text-end">${new Intl.NumberFormat('vi-VN').format(<?= $totalPrice ?>)} VNĐ</th></tr>
                <tr><td colspan="5"><div class="alert alert-warning mt-2">${data.error}</div></td></tr>
            `;
                } else {
                    tbody.innerHTML = `
                <tr><th colspan="4" class="text-end">Tạm tính:</th><th class="text-end"><?= number_format($totalPrice, 0, ',', '.') ?> VNĐ</th></tr>
                <tr><th colspan="4" class="text-end">Giảm giá:</th><th class="text-end text-danger">- ${data.formattedDiscount}</th></tr>
                <tr><th colspan="4" class="text-end">Tổng cộng sau giảm:</th><th class="text-end text-success">${data.formattedTotal}</th></tr>
            `;
                }
            });
    });
</script>
<?php include_once __DIR__ . '/../src/partials/footer.php'; ?>