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
                <table class="table table-bordered">
                    <thead>
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

</html>