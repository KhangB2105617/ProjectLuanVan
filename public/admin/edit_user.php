<?php
require_once __DIR__ . '/../../src/bootstrap.php';
use NL\User;

$user = new User($PDO);
$id = $_GET['id'];
$userData = $user->getById($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        ':username' => $_POST['username'],
        ':email' => $_POST['email'],
        ':role' => $_POST['role']
    ];
    if ($user->update($id, $data)) {
        header("Location: manage_users.php?success=1");
        exit();
    } else {
        $error = "Cập nhật người dùng thất bại.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa người dùng</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h1>Sửa người dùng</h1>
        <?php if (isset($error)): ?>
            <p class="text-danger"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label for="username">Tên người dùng</label>
                <input type="text" name="username" id="username" class="form-control" value="<?= htmlspecialchars($userData->username) ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($userData->email) ?>" required>
            </div>
            <div class="form-group">
                <label for="role">Vai trò</label>
                <select name="role" id="role" class="form-control" required>
                    <option value="customer" <?= $userData->role === 'customer' ? 'selected' : '' ?>>Khách hàng</option>
                    <option value="admin" <?= $userData->role === 'admin' ? 'selected' : '' ?>>Quản trị viên</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Cập nhật</button>
            <a href="manage_users.php" class="btn btn-secondary">Quay lại</a>
        </form>
    </div>
</body>
</html>
