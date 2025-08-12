<?php 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /unauthorized.php');
    exit;
}
require_once __DIR__ . '/../../src/bootstrap.php';

use NL\User;

$userModel = new User($PDO);
// Lấy cả user đã bị xóa (is_deleted = 1)
$users = $userModel->getAll();

include 'includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>
        <main class="col-md-10 ms-sm-auto px-md-4" style="margin-left: 17%;">
            <div class="pt-4">
                <h1 class="mb-4">Quản lý người dùng</h1>
                <a href="add_user.php" class="btn btn-success mb-3"><i class="fas fa-plus"></i> Thêm người dùng</a>
                <table id="productsTable" class="table table-bordered">
                    <thead class="table-primary">
                        <tr>
                            <th>ID</th>
                            <th>Tên</th>
                            <th>Email</th>
                            <th>Vai trò</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <?php $isDeleted = ($u->is_deleted == 1); ?>
                            <tr class="<?= $isDeleted ? 'table-secondary text-muted' : '' ?>" style="<?= $isDeleted ? 'opacity:0.5;' : '' ?>">
                                <td><?= $u->id ?></td>
                                <td><?= htmlspecialchars($u->username) ?></td>
                                <td><?= htmlspecialchars($u->email) ?></td>
                                <td><?= htmlspecialchars($u->role) ?></td>
                                <td>
                                    <?php if (!$isDeleted): ?>
                                        <a href="edit_user.php?id=<?= $u->id ?>" class="btn btn-warning btn-sm">Sửa</a>
                                        <a href="delete_user.php?id=<?= $u->id ?>" class="btn btn-danger btn-sm">Xóa</a>
                                    <?php else: ?>
                                        <a href="restore_user.php?id=<?= $u->id ?>" class="btn btn-success btn-sm">Khôi phục</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
</body>
<script>
    $(document).ready(function() {
        $('#productsTable').DataTable({
            "language": {
                "search": "Tìm kiếm:",
                "lengthMenu": "Hiển thị _MENU_ người dùng",
                "info": "Hiển thị từ _START_ đến _END_ trong tổng _TOTAL_ người dùng",
                "paginate": {
                    "first": "Đầu",
                    "last": "Cuối",
                    "next": "Tiếp",
                    "previous": "Trước"
                },
                "emptyTable": "Không có dữ liệu trong bảng",
                "zeroRecords": "Không tìm thấy người dùng phù hợp",
            }
        });
    });
</script>
</html>