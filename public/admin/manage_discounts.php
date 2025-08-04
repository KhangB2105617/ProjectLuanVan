<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../src/bootstrap.php';

$pageTitle = "Quản lý mã giảm giá";

$stmt = $PDO->query("SELECT * FROM discount_codes ORDER BY created_at ASC");
$discounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4" style="margin-left: 17%;">
            <div class="pt-4">
                <h1 class="mb-4">Quản lý mã giảm giá</h1>

                <a href="add_discount.php" class="btn btn-success mb-3">
                    <i class="fas fa-plus"></i> Thêm mã giảm giá
                </a>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover text-center align-middle">
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
                            <?php if (empty($discounts)): ?>
                                <tr>
                                    <td colspan="8" class="text-muted">Không có mã giảm giá nào.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($discounts as $discount): ?>
                                    <tr>
                                        <td><?= $discount['id'] ?></td>
                                        <td><?= htmlspecialchars($discount['code']) ?></td>
                                        <td><?= $discount['discount_type'] === 'percent' ? 'Phần trăm' : 'Cố định' ?></td>
                                        <td>
                                            <?= $discount['discount_type'] === 'percent'
                                                ? $discount['discount_value'] . '%'
                                                : number_format($discount['discount_value'], 0, ',', '.') . ' VNĐ' ?>
                                        </td>
                                        <td><?= $discount['max_usage'] ?? 'Không giới hạn' ?></td>
                                        <td><?= $discount['expired_at'] ? date('d/m/Y', strtotime($discount['expired_at'])) : 'Không giới hạn' ?></td>
                                        <td><?= date('d/m/Y', strtotime($discount['created_at'])) ?></td>
                                        <td>
                                            <a href="edit_discount.php?id=<?= $discount['id'] ?>" class="btn btn-warning btn-sm">
                                                <i class="fas fa-edit"></i> Sửa
                                            </a>
                                            <a href="delete_discount.php?id=<?= $discount['id'] ?>" class="btn btn-danger btn-sm" 
                                               onclick="return confirm('Bạn có chắc chắn muốn xóa mã giảm giá này?');">
                                                <i class="fas fa-trash"></i> Xóa
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
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