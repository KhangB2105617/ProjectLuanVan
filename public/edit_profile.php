<?php 
include_once __DIR__ . '/../src/partials/header.php';
require_once __DIR__ . '/../src/bootstrap.php';

use NL\User;

$user = new User($PDO);
$currentUser = $user->getById($_SESSION['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => $_POST['username'],
        'email' => $_POST['email'],
        'gender' => $_POST['gender'],
    ];

    if (!empty($_FILES['avatar']['name'])) {
        $avatarPath = 'admin/uploads/user/' . basename($_FILES['avatar']['name']);
        move_uploaded_file($_FILES['avatar']['tmp_name'], __DIR__ . '/../public/' . $avatarPath);
        $data['avatar'] = $avatarPath;
    }

    if ($user->updateProfile($_SESSION['id'], $data)) {
        echo "<div class='alert alert-success'>Cập nhật thành công!</div>";
        $currentUser = $user->getById($_SESSION['id']); // Cập nhật dữ liệu mới
    } else {
        echo "<div class='alert alert-danger'>Có lỗi xảy ra!</div>";
    }
}
?>

<main>
    <div class="container pt-3">
        <h2 class="text-center">Chỉnh sửa thông tin cá nhân</h2>
        <form method="POST" action="edit_profile.php" enctype="multipart/form-data" class="w-50 mx-auto">
            <div class="mb-3">
                <label for="username" class="form-label">Tên người dùng</label>
                <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($currentUser->username) ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($currentUser->email) ?>" required>
            </div>
            <div class="mb-3">
                <label for="gender" class="form-label">Giới tính</label>
                <select class="form-control" id="gender" name="gender">
                    <option value="Nam" <?= ($currentUser->gender == 'Nam') ? 'selected' : '' ?>>Nam</option>
                    <option value="Nữ" <?= ($currentUser->gender == 'Nữ') ? 'selected' : '' ?>>Nữ</option>
                    <option value="Khác" <?= ($currentUser->gender == 'Khác') ? 'selected' : '' ?>>Khác</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="avatar" class="form-label">Ảnh đại diện</label>
                <input type="file" class="form-control" id="avatar" name="avatar">
                <?php if (!empty($currentUser->avatar)): ?>
                    <img src="/<?= htmlspecialchars($currentUser->avatar) ?>" alt="Avatar" class="img-thumbnail mt-2" style="width: 100px;">
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary">Cập nhật</button>
        </form>
    </div>
</main>
