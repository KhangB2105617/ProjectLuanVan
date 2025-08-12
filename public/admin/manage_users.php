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

$user = new User($PDO);
$users = $user->getAll();

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
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user->id ?></td>
                                <td><?= htmlspecialchars($user->username) ?></td>
                                <td><?= htmlspecialchars($user->email) ?></td>
                                <td><?= htmlspecialchars($user->role) ?></td>
                                <td>
                                    <a href="edit_user.php?id=<?= $user->id ?>" class="btn btn-warning btn-sm">Sửa</a>
                                    <a href="delete_user.php?id=<?= $user->id ?>" class="btn btn-danger btn-sm">Xóa</a>
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
                "lengthMenu": "Hiển thị _MENU_ tin tức",
                "info": "Hiển thị từ _START_ đến _END_ trong tổng _TOTAL_ tin tức",
                "paginate": {
                    "first": "Đầu",
                    "last": "Cuối",
                    "next": "Tiếp",
                    "previous": "Trước"
                },
                "emptyTable": "Không có dữ liệu trong bảng",
                "zeroRecords": "Không tìm thấy sản phẩm phù hợp",
            }
        });
    });
</script>
</html>