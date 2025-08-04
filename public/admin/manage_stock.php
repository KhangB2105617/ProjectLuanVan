<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
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
    $import_price = isset($_POST['import_price']) ? (float)$_POST['import_price'] : 0;
    $export_price = isset($_POST['export_price']) ? (float)$_POST['export_price'] : 0;

    if ($product_id <= 0 || $quantity_change <= 0 || !in_array($change_type, ['in', 'out'])) {
        echo "<div class='alert alert-danger'>Dữ liệu không hợp lệ!</div>";
        exit;
    }

    $user_id = $_SESSION['user_id'] ?? null;

    // Cập nhật tồn kho
    $result = $stock->updateStockQuantity($product_id, $quantity_change, $change_type, $import_price, $export_price, $user_id);

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
    <?php include 'includes/header.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            <main class="col-md-10 ms-sm-auto px-md-4" style="margin-left: 17%;">
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
                    <div class="mb-3 import-group">
                        <label for="import_price" class="form-label">Giá nhập (VNĐ)</label>
                        <input type="number" class="form-control" name="import_price" min="0" step="100">
                    </div>
                    <div class="mb-3 export-group" style="display: none;">
                        <label for="export_price" class="form-label">Giá xuất (VNĐ)</label>
                        <input type="number" class="form-control" name="export_price" min="0" step="100">
                    </div>
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                </form>

                <a href="add_product.php" class="btn btn-success mb-3 mt-4"><i class="fas fa-plus"></i> Thêm sản phẩm</a>

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
                                <th>Thương hiệu</th>
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
                                    <td><?= htmlspecialchars($product->category_name) ?></td>
                                    <td><?= htmlspecialchars($product->brand_name) ?></td>
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
                                <th>Giá nhập/xuất</th>
                                <th>Thời gian nhập/xuất</th>
                                <th>Người thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $entry): ?>
                                <tr>
                                    <td><?= htmlspecialchars($entry->product_name) ?></td>
                                    <td><?= $entry->change_quantity ?></td>
                                    <td><?= $entry->change_type === 'in' ? 'Nhập kho' : 'Xuất kho' ?></td>
                                    <td>
                                        <?php if ($entry->change_type === 'in'): ?>
                                            Giá nhập: <?= number_format($entry->import_price ?? 0, 0, ',', '.') ?> VNĐ
                                        <?php elseif ($entry->change_type === 'out'): ?>
                                            Giá xuất: <?= number_format($entry->export_price ?? 0, 0, ',', '.') ?> VNĐ
                                        <?php endif; ?>
                                    </td>

                                    <td><?= $entry->change_date ?></td>
                                    <td><?= htmlspecialchars($entry->user_name ?? 'Không rõ') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
        </div>
    </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

<script>
    document.querySelector('select[name="change_type"]').addEventListener('change', function() {
        const type = this.value;
        document.querySelector('.import-group').style.display = (type === 'in') ? 'block' : 'none';
        document.querySelector('.export-group').style.display = (type === 'out') ? 'block' : 'none';
    });

    document.querySelector('select[name="change_type"]').dispatchEvent(new Event('change'));
</script>

</html>