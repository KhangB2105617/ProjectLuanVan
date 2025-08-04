<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /unauthorized.php');
    exit;
}
require_once __DIR__ . '/../../src/bootstrap.php';

use NL\Product;

$product = new Product($PDO);
$products = $product->getAll();
$pageTitle = "Quản lý sản phẩm";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4" style="margin-left: 17%;">
            <div class="pt-4">
                <h1 class="mb-4">Quản lý sản phẩm</h1>
                <a href="add_product.php" class="btn btn-success mb-3"><i class="fas fa-plus"></i> Thêm sản phẩm</a>
                <a href="deleted_products.php" class="btn btn-outline-secondary mb-3"><i class="fas fa-trash"></i> Xem sản phẩm đã xóa</a>
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
                                    <td><img src="/assets/img/<?= $product->image; ?>" style="max-width: 100px;" alt="<?= htmlspecialchars($product->name); ?>"></td>
                                    <td><?= $product->id ?></td>
                                    <td><?= htmlspecialchars($product->name) ?></td>
                                    <td><?= number_format($product->price, 0, ',', '.') ?> VNĐ</td>
                                    <td><?= $product->quantity ?></td>
                                    <td><?= htmlspecialchars($product->category_name) ?></td>
                                    <td><?= htmlspecialchars($product->brand_name) ?></td>
                                    <td>
                                        <a href="edit_product.php?id=<?= $product->id ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Sửa</a>
                                        <a href="delete_product.php?id=<?= $product->id ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?');"><i class="fas fa-trash"></i> Xóa</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>