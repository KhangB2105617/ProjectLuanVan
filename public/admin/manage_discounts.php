<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../src/bootstrap.php';

// Lấy danh sách mã giảm giá từ CSDL
$stmt = $PDO->query("SELECT * FROM discount_codes ORDER BY created_at DESC");
$discounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý mã giảm giá</title>
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
                        <li class="nav-item"><a class="nav-link text-dark" href="manage_discounts.php">Mã khuyến mãi</a></li>
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
        <h1 class="mb-4">Quản lý mã giảm giá</h1>
        <a href="add_discount.php" class="btn btn-success mb-3"><i class="fas fa-plus"></i> Thêm mã giảm giá</a>
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Mã</th>
                        <th>Loại</th>
                        <th>Giá trị</th>
                        <th>Số lượng</th>
                        <th>Hết hạn</th>
                        <th>Ngày tạo</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($discounts as $discount): ?>
                        <tr>
                            <td><?= $discount['id'] ?></td>
                            <td><?= htmlspecialchars($discount['code']) ?></td>
                            <td><?= $discount['discount_type'] == 'percent' ? 'Phần trăm' : 'Cố định' ?></td>
                            <td>
                                <?= $discount['discount_type'] == 'percent' ? $discount['discount_value'] . '%' : number_format($discount['discount_value'], 0, ',', '.') . ' VNĐ' ?>
                            </td>
                            <td><?= $discount['max_usage'] ?></td>
                            <td><?= $discount['expired_at'] ? date('d/m/Y', strtotime($discount['expired_at'])) : 'Không giới hạn' ?></td>
                            <td><?= date('d/m/Y', strtotime($discount['created_at'])) ?></td>
                            <td>
                                <a href="edit_discount.php?id=<?= $discount['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Sửa</a>
                                <a href="delete_discount.php?id=<?= $discount['id'] ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Bạn có chắc chắn muốn xóa mã giảm giá này?');">
                                   <i class="fas fa-trash"></i> Xóa
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($discounts)): ?>
                        <tr><td colspan="8" class="text-center">Không có mã nào.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>