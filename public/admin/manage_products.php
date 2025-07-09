<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../src/bootstrap.php';

use NL\Product;

$product = new Product($PDO);
$products = $product->getAll();
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

    <!-- Nội dung quản lý sản phẩm -->
    <div class="container mt-4">
        <h1 class="mb-4">Quản lý sản phẩm</h1>
        <a href="add_product.php" class="btn btn-success mb-3"><i class="fas fa-plus"></i> Thêm sản phẩm</a>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>Hình ảnh</th>
                        <th>ID</th>
                        <th>Tên sản phẩm</th>
                        <th>Giá</th>
                        <th>Số lượng</th>
                        <th>Danh mục</th>
                        <th>Brand</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><img src="/assets/img/<?= ($product->image); ?>" style="max-width: 100px; height: auto; object-fit: contain;" alt="<?= htmlspecialchars($product->name); ?>"></td>
                            <td><?= $product->id ?></td>
                            <td><?= htmlspecialchars($product->name) ?></td>
                            <td><?= number_format($product->price, 0, ',', '.') ?> VNĐ</td>
                            <td><?= $product->quantity ?></td>
                            <td><?= htmlspecialchars($product->category) ?></td>
                            <td><?= htmlspecialchars($product->brand) ?></td>
                            <td>
                                <a href="edit_product.php?id=<?= $product->id ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Sửa</a>
                                <a href="delete_product.php?id=<?= $product->id ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?');">
                                    <i class="fas fa-trash"></i> Xóa
                                </a>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bootstrap JS và các plugin cần thiết -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>