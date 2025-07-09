<?php 
require_once __DIR__ . '/../../src/bootstrap.php';
use NL\Order;

$order = new Order($PDO);

$type = isset($_GET['type']) ? $_GET['type'] : 'month'; // Mặc định hiển thị theo tháng
$salesData = $order->getTotalSalesReport($type);
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
        <h1>Báo cáo doanh thu</h1>
        <form method="GET">
            <select name="type" class="form-control w-25 d-inline">
                <option value="day" <?= $type == 'day' ? 'selected' : '' ?>>Theo ngày</option>
                <option value="month" <?= $type == 'month' ? 'selected' : '' ?>>Theo tháng</option>
            </select>
            <button type="submit" class="btn btn-primary">Xem</button>
        </form>

        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>Thời gian</th>
                    <th>Tổng doanh thu (VND)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($salesData as $data): ?>
                    <tr>
                        <td><?= htmlspecialchars($data->time_period) ?></td>
                        <td><?= number_format($data->total_sales, 0, ',', '.') ?> đ</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
