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
        $_SESSION['success_message'] = "Cập nhật tồn kho thành công!";
        header("Location: manage_stock.php");
        exit;
    } else {
        $_SESSION['error_message'] = "Số lượng sản phẩm không đủ để xuất kho hoặc có lỗi xảy ra!";
        header("Location: manage_stock.php");
        exit;
    }
}

// Lấy lịch sử thay đổi kho
$productHistories = $stock->getAllStockHistoryGroupedByProduct();
$pageTitle = "Quản lý Kho hàng";
include 'includes/header.php';
?>
<?php
$toastType = '';
$toastMessage = '';
if (isset($_SESSION['success_message'])) {
    $toastType = 'success';
    $toastMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $toastType = 'danger';
    $toastMessage = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-md-4 pt-4" style="margin-left: 17%;">
            <h1><?= $pageTitle ?></h1>

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
                    <label for="quantity" class="form-label">Số lượng</label>
                    <input type="number" class="form-control" name="quantity" required min="1">
                </div>
                <div class="mb-3">
                    <label for="change_type" class="form-label">Hình thức</label>
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
                                    <form action="delete_product.php" method="POST" onsubmit="return confirm('Xác nhận xóa sản phẩm này?');" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= $product->id ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Xóa
                                        </button>
                                    </form>

                                    <?php if (!empty($productHistories[$product->id])): ?>
                                        <button class="btn btn-info btn-sm mt-1" type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#history-<?= $product->id ?>"
                                            aria-expanded="false"
                                            aria-controls="history-<?= $product->id ?>">
                                            <i class="fa-solid fa-house"></i> Lịch sử kho
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Lịch sử thay đổi kho cho sản phẩm này -->
                            <?php if (!empty($productHistories[$product->id])): ?>
                                <tr class="collapse" id="history-<?= $product->id ?>">
                                    <td colspan="8">
                                        <div class="card card-body">
                                            <table class="table table-sm table-bordered mt-2 mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Loại</th>
                                                        <th>Số lượng</th>
                                                        <th>Đơn giá</th>
                                                        <th>Thời gian</th>
                                                        <th>Người thao tác</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($productHistories[$product->id] as $entry): ?>
                                                        <tr>
                                                            <td><?= $entry->change_type === 'in' ? 'Nhập Kho' : 'Xuất Kho' ?></td>
                                                            <td><?= $entry->change_quantity ?></td>
                                                            <td>
                                                                <?php if ($entry->change_type === 'in'): ?>
                                                                    <?= number_format($entry->import_price ?? 0, 0, ',', '.') ?> VNĐ
                                                                <?php else: ?>
                                                                    <?= number_format($entry->export_price ?? 0, 0, ',', '.') ?> VNĐ
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?= $entry->change_date ?></td>
                                                            <td><?= htmlspecialchars($entry->user_name ?? 'Không rõ') ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
    </div>
</div>
<?php if (!empty($toastMessage)): ?>
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999">
        <div id="liveToast" class="toast align-items-center text-white bg-<?= $toastType ?> border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <?= htmlspecialchars($toastMessage) ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
<?php endif; ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.querySelector('select[name="change_type"]').addEventListener('change', function() {
        const type = this.value;
        document.querySelector('.import-group').style.display = (type === 'in') ? 'block' : 'none';
        document.querySelector('.export-group').style.display = (type === 'out') ? 'block' : 'none';
    });

    document.querySelector('select[name="change_type"]').dispatchEvent(new Event('change'));
</script>
<script>
    // Tự động ẩn toast sau 3 giây
    const toastEl = document.getElementById('liveToast');
    if (toastEl) {
        const toast = new bootstrap.Toast(toastEl, {
            delay: 3000
        });
        toast.show();
    }
</script>