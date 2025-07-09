<?php
require_once __DIR__ . '/../../src/bootstrap.php';
use NL\Order;

$order = new Order($PDO);

// Lấy tất cả đơn đặt hàng, bao gồm email, địa chỉ và số điện thoại
$orders = $order->getAllOrders();  // Giả sử phương thức này đã lấy đủ thông tin về đơn hàng

$error = '';
$success = '';

// Nếu người dùng gửi form để cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['status'];

    // Cập nhật trạng thái đơn hàng
    if ($order->updateOrderStatus($orderId, $newStatus)) {
        // Nếu cập nhật thành công
        $success = "Cập nhật trạng thái đơn hàng thành công!";
    } else {
        // Nếu cập nhật thất bại
        $error = "Cập nhật trạng thái vận chuyển thất bại.";
    }

    // Tự động làm mới danh sách đơn hàng sau khi cập nhật
    $orders = $order->getAllOrders(); // Lấy lại danh sách đơn hàng mới nhất
}

// Nếu người dùng gửi yêu cầu xóa đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order_id'])) {
    $deleteOrderId = $_POST['delete_order_id'];

    // Xóa đơn hàng
    if ($order->deleteOrder($deleteOrderId)) {
        // Nếu xóa thành công
        $success = "Đơn hàng đã được xóa thành công!";
    } else {
        // Nếu xóa thất bại
        $error = "Xóa đơn hàng thất bại.";
    }

    // Tự động làm mới danh sách đơn hàng sau khi xóa
    $orders = $order->getAllOrders(); // Lấy lại danh sách đơn hàng mới nhất
}
?>


<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Cửa hàng Classic cung cấp các sản phẩm thiết bị văn phòng chất lượng cao với dịch vụ khách hàng tốt nhất.">
    <meta name="keywords" content="thiết bị văn phòng, máy in, máy quét, sản phẩm văn phòng">
    <title>Quản lý sản phẩm</title>
    <link rel="icon" href="assets/img/vector-shop-icon-png_302739.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <!-- Header -->
    <header id="header" style="background-color: burlywood;">
    <div class="container-fluid">
        <div class="d-flex align-items-center" style="padding: 0;">
            <!-- Logo -->
            <div class="col-lg-2 d-flex justify-content-start m-0">
                <div class="header-logo">
                    <a href="../index.php">
                        <img src="/assets/img/Logo/logo-ngang.png" alt="Logo Shop" width="150" height="60">
                    </a>
                </div>
            </div>
            
            <!-- Navigation Bar -->
            <div id="nav" class="col-lg-7 d-flex justify-content-start m-0">
                <nav class="navbar navbar-expand-lg navbar-light" style="background-color: burlywood; padding: 0;">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item"><a class="nav-link text-dark" href="../index.php">Trang chủ</a></li>
                        <li class="nav-item"><a class="nav-link text-dark" href="manage_products.php">Sản Phẩm</a></li>
                        <li class="nav-item"><a class="nav-link text-dark" href="manage_users.php">Người dùng</a></li>
                        <li class="nav-item"><a class="nav-link text-dark" href="manage_shipping.php">Đơn hàng</a></li>
                        <li class="nav-item"><a class="nav-link text-dark" href="manage_stock.php">Quản lý kho</a></li>
                        <li class="nav-item"><a class="nav-link text-dark" href="sales_report.php">Thống kê doanh số</a></li>
                    </ul>
                </nav>
            </div>

            <!-- Account Info -->
            <div class="col-lg-2 d-flex justify-content-end m-0">
                <div class="header-account">
                    <ul class="nav">
                        <?php if (isset($_SESSION['username'])): ?>
                            <li class="nav-item">
                                <a class="nav-link text-dark fw-bold" href="/admin/settingadmin.php"><?php echo $_SESSION['username']; ?></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-dark" href="/logout.php">Đăng xuất</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link text-dark" href="/login.php">Đăng nhập</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</header>
    <div class="container mt-4">
        <h1>Quản lý vận chuyển đơn đặt hàng</h1>

        <!-- Hiển thị thông báo thành công hoặc lỗi -->
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <table class="table table-bordered">
        <thead>
    <tr>
        <th>ID Đơn hàng</th>
        <th>Tên khách hàng</th>
        <th>Email</th>
        <th>Địa chỉ</th>
        <th>Số điện thoại</th>
        <th>Sản phẩm</th>
        <th>Số lượng</th>
        <th>Giá</th> <!-- Thêm cột giá -->
        <th>Trạng thái</th>
        <th>Hành động</th>
        <th>Xóa</th>
    </tr>
</thead>
<tbody>
    <?php foreach ($orders as $order): ?>
    <tr>
        <td><?= htmlspecialchars($order->id) ?></td>
        <td><?= htmlspecialchars($order->customer_name) ?></td>
        <td><?= htmlspecialchars($order->customer_email) ?></td>
        <td><?= htmlspecialchars($order->customer_address) ?></td>
        <td><?= htmlspecialchars($order->customer_phone) ?></td>
        <td><?= htmlspecialchars($order->product_name) ?></td>
        <td><?= htmlspecialchars($order->quantity) ?></td>
        <td><?= number_format($order->price, 2) ?> đ</td> <!-- Hiển thị giá -->
        <td><?= htmlspecialchars($order->status) ?></td>
        <td>
            <form method="post" style="display:inline;">
                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order->id) ?>">
                <select name="status" class="form-control" required>
                    <option value="Đang xử lý" <?= $order->status == 'Đang xử lý' ? 'selected' : '' ?>>Đang xử lý</option>
                    <option value="Đang vận chuyển" <?= $order->status == 'Đang vận chuyển' ? 'selected' : '' ?>>Đang vận chuyển</option>
                    <option value="Đã giao" <?= $order->status == 'Đã giao' ? 'selected' : '' ?>>Đã giao</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm mt-2">Cập nhật</button>
            </form>
        </td>
        <td>
            <form method="post" style="display:inline;">
                <input type="hidden" name="delete_order_id" value="<?= htmlspecialchars($order->id) ?>">
                <button type="submit" class="btn btn-danger btn-sm mt-2">Xóa</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>

        </table>
    </div>

    <!-- Bootstrap JS (optional) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

