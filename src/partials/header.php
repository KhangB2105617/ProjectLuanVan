<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Cửa hàng Classic cung cấp các sản phẩm thiết bị văn phòng chất lượng cao với dịch vụ khách hàng tốt nhất.">
    <meta name="keywords" content="thiết bị văn phòng, máy in, máy quét, sản phẩm văn phòng">
    <title>Store đồng hồ</title>
    <link rel="icon" href="assets/img/vector-shop-icon-png_302739.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>

   <!-- Header -->
   <header id="header">
    <div class="container-fluid py-2">
        <div class="d-flex align-items-center justify-content-between">
            <!-- Logo -->
            <div class="header-logo">
                <a href="index.php">
                    <img src="assets/img/Logo/logo-ngang.png" alt="Logo Shop" width="150" height="60">
                </a>
            </div>

            <!-- Navigation Bar -->
            <nav class="navbar navbar-expand-lg navbar-light">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item"><a class="nav-link fw-bold" href="index.php">Trang chủ</a></li>
                        <li class="nav-item"><a class="nav-link fw-bold" href="product.php">Đồng hồ</a></li>
                        <li class="nav-item"><a class="nav-link fw-bold" href="contact.php">Liên hệ</a></li>
                        <li class="nav-item"><a class="nav-link fw-bold" href="about.php">Về chúng tôi</a></li>
                    </ul>
                </div>
            </nav>

            <!-- Search Bar -->
            <form class="d-flex search-bar" role="search" action="search.php" method="get">
                <div class="input-group">
                    <input type="text" name="q" class="form-control" placeholder="Tìm kiếm sản phẩm...">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <!-- User Account -->
            <div class="d-flex align-items-center">
    <!-- Cart -->
    <div class="cart-icon me-3">
        <a href="cart.php" class="btn position-relative">
            <i class="fas fa-shopping-cart"></i>
            <span class="ms-1">Giỏ hàng</span>
            <?php if (!empty($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?= count($_SESSION['cart']); ?>
                </span>
            <?php endif; ?>
        </a>
    </div>

    <!-- User Account -->
    <div class="header-account dropdown">
        <a class="nav-link text-dark fw-bold dropdown-toggle d-flex align-items-center" id="accountDropdown" role="button" data-bs-toggle="dropdown">
            <i class="fas fa-user-circle fa-2x me-1"></i> <span>Tài khoản</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
            <?php if (isset($_SESSION['username'])): ?>
                <li><a class="dropdown-item" href="">Xin chào, <?php echo $_SESSION['username']; ?></a></li>
                <?php if (isset($_SESSION['role'])): ?>
                    <?php if ($_SESSION['role'] === 'customer'): ?>
                        <li><a class="dropdown-item" href="edit_profile.php">Chỉnh sửa thông tin</a></li>
                        <li><a class="dropdown-item" href="orders.php">Đơn hàng của tôi</a></li>
                    <?php elseif ($_SESSION['role'] === 'admin'): ?>
                        <li><a class="dropdown-item" href="admin/settingadmin.php">Quản lý hệ thống</a></li>
                    <?php endif; ?>
                <?php endif; ?>
                <li><a class="dropdown-item" href="logout.php">Đăng xuất</a></li>
            <?php else: ?>
                <li><a class="dropdown-item" href="register.php">Đăng ký</a></li>
                <li><a class="dropdown-item" href="login.php">Đăng nhập</a></li>
            <?php endif; ?>
        </ul>
    </div>
</div>

        </div>
    </div>
</header>
