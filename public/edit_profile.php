<?php
include_once __DIR__ . '/../src/partials/header.php';
require_once __DIR__ . '/../src/bootstrap.php';

use NL\User;

$user = new User($PDO);
$currentUser = $user->getById($_SESSION['id']);

$errors = [];
$fieldErrors = [];
$highlightPassword = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => trim($_POST['username']),
        'email' => trim($_POST['email']),
        'gender' => $_POST['gender'] ?? '',
        'address' => $_POST['address'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'birthday' => $_POST['birthday'] ?? null,
    ];

    // Kiểm tra ngày sinh hợp lệ
    if (!empty($data['birthday']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['birthday'])) {
        $errors[] = "Ngày sinh không hợp lệ! Định dạng đúng là yyyy-mm-dd.";
    }

    // Xử lý mật khẩu nếu có nhập
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($oldPassword || $newPassword || $confirmPassword) {
        if (!$oldPassword || !$newPassword || !$confirmPassword) {
            $errors[] = "Vui lòng nhập đầy đủ thông tin mật khẩu.";
            $highlightPassword = true;
        } elseif (!password_verify($oldPassword, $currentUser->password)) {
            $errors[] = "Mật khẩu hiện tại không chính xác!";
            $highlightPassword = true;
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = "Xác nhận mật khẩu mới không khớp!";
            $highlightPassword = true;
        } else {
            $data['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }
    }

    // Xử lý avatar nếu có
    if (!empty($_FILES['avatar']['name'])) {
        $fileName = basename($_FILES['avatar']['name']);
        $avatarPath = 'admin/uploads/user/' . time() . '_' . preg_replace('/[^a-zA-Z0-9\.\-_]/', '_', $fileName);
        $uploadSuccess = move_uploaded_file($_FILES['avatar']['tmp_name'], __DIR__ . '/../public/' . $avatarPath);
        if ($uploadSuccess) {
            $data['avatar'] = $avatarPath;
        } else {
            $errors[] = "Không thể tải lên ảnh đại diện.";
        }
    }

    // Nếu không có lỗi, cập nhật thông tin
    if (empty($errors)) {
        if ($user->updateProfile($_SESSION['id'], $data)) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    Cập nhật thành công!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
                  </div>';
            $currentUser = $user->getById($_SESSION['id']); // Refresh dữ liệu mới
            $_SESSION['username'] = $currentUser->username;
            $_SESSION['avatar'] = $currentUser->avatar ?? null;
        } else {
            $errors[] = "Có lỗi xảy ra khi cập nhật thông tin.";
        }
    }

    // Hiển thị lỗi nếu có
    foreach ($errors as $error) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                ' . htmlspecialchars($error) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
              </div>';
    }
}
?>

<main>
    <div class="container pt-3">
        <h2 class="text-center">Thông tin cá nhân</h2>
        <form method="POST" action="edit_profile.php" enctype="multipart/form-data" class="w-50 mx-auto">
            <div class="mb-3">
                <label for="username" class="form-label">Họ tên</label>
                <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($currentUser->username) ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($currentUser->email) ?>" required>
            </div>
            <div class="mb-3">
                <label for="address" class="form-label">Địa chỉ</label>
                <input type="text" class="form-control" id="address" name="address" value="<?= htmlspecialchars($currentUser->address ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="phone" class="form-label">Số điện thoại</label>
                <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($currentUser->phone ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="birthday" class="form-label">Ngày sinh</label>
                <input type="date" class="form-control" id="birthday" name="birthday" value="<?= htmlspecialchars($currentUser->birthday ?? '') ?>">
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
                <label for="old_password" class="form-label">Mật khẩu hiện tại</label>
                <input type="password" class="form-control <?= $highlightPassword ? 'is-invalid' : '' ?>" id="old_password" name="old_password">
            </div>
            <div class="mb-3">
                <label for="new_password" class="form-label">Mật khẩu mới</label>
                <input type="password" class="form-control" id="new_password" name="new_password">
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
            </div>

            <div class="mb-3">
                <label for="avatar" class="form-label">Ảnh đại diện</label>
                <input type="file" class="form-control" id="avatarInput" name="avatar">
                <div class="mt-2">
                    <img id="avatarPreview"
                        src="/<?= htmlspecialchars($currentUser->avatar ?? 'default.png') ?>"
                        alt="Avatar preview"
                        class="img-thumbnail"
                        style="width: 100px;">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Cập nhật</button>
        </form>

    </div>
</main>
<script>
    document.getElementById('avatarInput').addEventListener('change', function(event) {
        const file = event.target.files[0];
        const preview = document.getElementById('avatarPreview');

        if (file && preview) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>