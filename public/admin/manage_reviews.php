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

// Truy vấn danh sách bình luận
$stmt = $PDO->prepare("
    SELECT r.*, p.name AS product_name 
    FROM reviews r 
    JOIN products p ON r.product_id = p.id 
    ORDER BY r.created_at DESC
");
$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_OBJ);
include 'includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-md-4" style="margin-left: 17%;">
            <div class="container mt-5">
                <h2 class="mb-4">Quản lý đánh giá sản phẩm</h2>

                <?php if (isset($_GET['success']) || isset($_GET['error'])): ?>
                    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;">
                        <div class="toast align-items-center text-white <?= isset($_GET['success']) ? 'bg-success' : 'bg-danger' ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
                            <div class="d-flex">
                                <div class="toast-body">
                                    <?= isset($_GET['success']) ? '✅ Xóa bình luận thành công.' : '❌ Có lỗi xảy ra khi xóa bình luận.' ?>
                                </div>
                                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <table class="table table-bordered table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>ID</th>
                            <th>Sản phẩm</th>
                            <th>Người đánh giá</th>
                            <th>Số sao</th>
                            <th>Bình luận</th>
                            <th>Ngày tạo</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td><?= $review->id ?></td>
                                <td><?= htmlspecialchars($review->product_name) ?></td>
                                <td><?= htmlspecialchars($review->customer_name) ?></td>
                                <td><?= $review->rating ?> ⭐</td>
                                <td><?= nl2br(htmlspecialchars($review->comment)) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($review->created_at)) ?></td>
                                <td>
                                    <form method="post" action="delete_review.php" onsubmit="return confirm('Xác nhận xóa bình luận này?');">
                                        <input type="hidden" name="id" value="<?= $review->id ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">🗑 Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($reviews)): ?>
                            <tr>
                                <td colspan="7" class="text-center">Chưa có bình luận nào.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
 const toastEl = document.querySelector('.toast');
if (toastEl) {
    const bsToast = new bootstrap.Toast(toastEl, {
        delay: 1500,
        autohide: true
    });
    bsToast.show();
}
</script>
