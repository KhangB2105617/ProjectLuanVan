<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header('Location: /unauthorized.php');
    exit;
}
require_once __DIR__ . '/../../src/bootstrap.php';

use NL\Product;

$product = new Product($PDO);
$keyword = $_GET['keyword'] ?? null;
if ($keyword) {
    $products = $product->searchProducts($keyword);
} else {
    $products = $product->getAll();
}
$pageTitle = "Quản lý sản phẩm";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4" style="margin-left: 17%;">
            <div class="pt-4">
                <h1 class="mb-4">Quản lý sản phẩm</h1>
                <form class="row g-3 mb-3" method="GET" style="max-width: 400px;">
                    <div class="col-8">
                        <input type="text" name="keyword" class="form-control form-control-sm" placeholder="Tìm sản phẩm..." value="<?= isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : '' ?>">
                    </div>
                    <div class="col-3">
                        <button type="submit" class="btn btn-sm btn-primary w-100">Tìm kiếm</button>
                    </div>
                </form>

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
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">Không tìm thấy sản phẩm nào.</td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><img src="/assets/img/<?= $product->image; ?>" style="max-width: 100px;" alt="<?= htmlspecialchars($product->name); ?>"></td>
                                    <td><?= $product->id ?></td>
                                    <td><?= htmlspecialchars(string: $product->name) ?></td>
                                    <td><?= number_format($product->price, 0, ',', '.') ?> VNĐ</td>
                                    <td><?= $product->quantity ?></td>
                                    <td><?= htmlspecialchars($product->category_name) ?></td>
                                    <td><?= htmlspecialchars($product->brand_name) ?></td>
                                    <td>
                                        <?php if ($product->is_visible): ?>
                                            <a href="toggle_visibility.php?id=<?= $product->id ?>&visible=1"
                                                class="btn btn-success btn-sm"
                                                onclick="return confirm('Bạn có chắc muốn ẨN sản phẩm này không?');">
                                                Hiển thị
                                            </a>
                                        <?php else: ?>
                                            <a href="toggle_visibility.php?id=<?= $product->id ?>&visible=0"
                                                class="btn btn-secondary btn-sm"
                                                onclick="return confirm('Bạn có chắc muốn HIỂN THỊ lại sản phẩm này không?');">
                                                Ẩn
                                            </a>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <a href="edit_product.php?id=<?= $product->id ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Sửa</a>
                                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal"
                                            data-id="<?= $product->id ?>">
                                            <i class="fas fa-trash"></i> Xóa
                                        </button>
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
<!-- Modal xác nhận xóa -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="deleteForm" method="post" action="delete_product.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Xác nhận xóa sản phẩm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    Bạn có chắc chắn muốn xóa sản phẩm này?
                </div>
                <input type="hidden" name="id" id="deleteProductId">
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger">Xóa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var deleteModal = document.getElementById('deleteModal');
        deleteModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var productId = button.getAttribute('data-id');
            document.getElementById('deleteProductId').value = productId;
        });
    });
</script>

</body>

</html>