<?php
require_once __DIR__ . '/../../src/bootstrap.php';
use NL\Product;

$product = new Product($PDO);
$deletedProducts = $product->getDeleted(); // Lấy danh sách sản phẩm đã bị ẩn
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sản phẩm đã xóa</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h1 class="mb-4">Danh sách sản phẩm đã xóa</h1>
    <a href="manage_products.php" class="btn btn-secondary mb-3">← Quay về quản lý sản phẩm</a>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
            <tr>
                <th>ID</th>
                <th>Tên</th>
                <th>Giá</th>
                <th>Hình ảnh</th>
                <th>Thời gian xóa</th>
                <th>Hành động</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($deletedProducts as $p): ?>
                <tr>
                    <td><?= $p->id ?></td>
                    <td><?= htmlspecialchars($p->name) ?></td>
                    <td><?= number_format($p->price, 0, ',', '.') ?> VNĐ</td>
                    <td><img src="/assets/img/<?= $p->image ?>" width="80"></td>
                    <td><?= $p->deleted_at ?></td>
                    <td>
                        <a href="restore_product.php?id=<?= $p->id ?>" class="btn btn-success btn-sm">Khôi phục</a>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
