<?php 
session_start(); // Khởi tạo session

require_once __DIR__ . '/../../src/bootstrap.php';
use NL\Product;
use NL\Stock;

$product = new Product($PDO);
$stock = new Stock($PDO);

// Lấy danh sách sản phẩm
$products = $product->getAll();

// Kiểm tra dữ liệu đầu vào khi submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra dữ liệu đầu vào
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity_change = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    $change_type = isset($_POST['change_type']) ? $_POST['change_type'] : '';

    if ($product_id <= 0 || $quantity_change <= 0 || !in_array($change_type, ['in', 'out'])) {
        echo "<div class='alert alert-danger'>Dữ liệu không hợp lệ!</div>";
        exit;
    }

    // Cập nhật tồn kho
    $result = $stock->updateStockQuantity($product_id, $quantity_change, $change_type);

    if ($result) {
        echo "<div class='alert alert-success'>Cập nhật tồn kho thành công!</div>";
        header("Location: manage_stock.php"); // Chuyển hướng lại trang quản lý kho
        exit;
    } else {
        echo "<div class='alert alert-danger'>Số lượng sản phẩm không đủ để xuất kho hoặc có lỗi xảy ra!</div>";
        exit;
    }
}

// Lấy lịch sử thay đổi kho
$history = $stock->getStockHistory();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Quản lý kho hàng">
    <meta name="keywords" content="quản lý kho, tồn kho, nhập kho, xuất kho">
    <title>Quản lý kho hàng</title>
    <link rel="icon" href="assets/img/vector-shop-icon-png_302739.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
        <h1 class="mb-4">Quản lý kho hàng</h1>

        <!-- Cập nhật tồn kho -->
        <h2>Cập nhật tồn kho</h2>
        <form action="manage_stock.php" method="POST">
            <div class="mb-3">
                <label for="product_id" class="form-label">Chọn sản phẩm</label>
                <select class="form-select" name="product_id" required>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= $product->id ?>"><?= htmlspecialchars($product->name) ?> - Tồn kho: <?= $product->quantity ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="quantity" class="form-label">Số lượng thay đổi</label>
                <input type="number" class="form-control" name="quantity" required min="1">
            </div>
            <div class="mb-3">
                <label for="change_type" class="form-label">Loại thay đổi</label>
                <select class="form-select" name="change_type" required>
                    <option value="in">Nhập kho</option>
                    <option value="out">Xuất kho</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Cập nhật</button>
        </form>

        <a href="add_product.php" class="btn btn-success mb-3 mt-4"><i class="fas fa-plus"></i> Thêm sản phẩm</a>
        
        <!-- Table to display products and stock changes -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>Hình ảnh</th>
                        <th>ID</th>
                        <th>Tên sản phẩm</th>
                        <th>Tồn kho</th>
                        <th>Giá</th>
                        <th>Danh mục</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><img src="/assets/img/<?= ($product->image); ?>" style="max-width: 100px; height: auto; object-fit: contain;" alt="<?= htmlspecialchars($product->name); ?>"></td>
                        <td><?= $product->id ?></td>
                        <td><?= htmlspecialchars($product->name) ?></td>
                        <td><?= $product->quantity ?></td>
                        <td><?= number_format($product->price, 0, ',', '.') ?> VNĐ</td>
                        <td><?= htmlspecialchars($product->category) ?></td>
                        <td><?= htmlspecialchars($product->brand) ?></td>
                        <td>
                            <a href="edit_product.php?id=<?= $product->id ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Sửa</a>
                            <a href="delete_product.php?id=<?= $product->id ?>" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Xóa</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h2 class="mt-4">Lịch sử thay đổi kho</h2>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Số lượng thay đổi</th>
                        <th>Loại thay đổi</th>
                        <th>Thời gian</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $entry): ?>
                    <tr>
                        <td><?= htmlspecialchars($entry->product_name) ?></td>
                        <td><?= $entry->change_quantity ?></td>
                        <td><?= $entry->change_type === 'in' ? 'Nhập kho' : 'Xuất kho' ?></td>
                        <td><?= $entry->change_date ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
