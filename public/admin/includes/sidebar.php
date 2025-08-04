<?php if (session_status() == PHP_SESSION_NONE) session_start(); ?> 

<nav id="sidebar" class="bg-light sidebar position-fixed" style="height: 100vh; width: 220px; transition: all 0.3s;">
    <div class="text-center py-3">
        <a href="/index.php">
            <img src="/assets/img/Logo/logo-ngang.png" alt="Logo" width="150" class="logo-text">
        </a>
    </div>
    <ul class="nav flex-column text-start px-2" id="sidebarMenu">
        <?php if ($_SESSION['role'] !== 'staff'): ?>
            <li class="nav-item"><a class="nav-link text-dark" href="manage_products.php"><i class="fas fa-box"></i> <span>Sản phẩm</span></a></li>
            <li class="nav-item"><a class="nav-link text-dark" href="manage_users.php"><i class="fas fa-users"></i> <span>Người dùng</span></a></li>
        <?php endif; ?>

        <li class="nav-item"><a class="nav-link text-dark" href="manage_shipping.php"><i class="fas fa-truck"></i> <span>Đơn hàng</span></a></li>
        <li class="nav-item"><a class="nav-link text-dark" href="manage_stock.php"><i class="fas fa-warehouse"></i> <span>Kho</span></a></li>
        <li class="nav-item"><a class="nav-link text-dark" href="manage_reviews.php"><i class="fas fa-comment"></i> <span>Bình luận</span></a></li>
        <li class="nav-item"><a class="nav-link text-dark" href="sales_report.php"><i class="fas fa-chart-line"></i> <span>Thống kê</span></a></li>
        <li class="nav-item"><a class="nav-link text-dark" href="manage_discounts.php"><i class="fas fa-tag"></i> <span>Khuyến mãi</span></a></li>

        <li class="nav-item mt-4 px-2">
            <?php if (isset($_SESSION['username'])): ?>
                <div class="fw-bold"><span><?= $_SESSION['username']; ?></span></div>
                <a class="nav-link text-danger px-0" href="/logout.php"><i class="fas fa-sign-out-alt"></i> <span>Đăng xuất</span></a>
            <?php else: ?>
                <a class="nav-link" href="/login.php"><i class="fas fa-sign-in-alt"></i> <span>Đăng nhập</span></a>
            <?php endif; ?>
        </li>
    </ul>
</nav>