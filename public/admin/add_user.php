<?php
require_once __DIR__ . '/../../src/bootstrap.php';
use NL\User;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = new User($PDO);
    $data = [
        ':username' => $_POST['username'],
        ':email' => $_POST['email'],
        ':password' => password_hash($_POST['password'], PASSWORD_BCRYPT),
        ':role' => $_POST['role']
    ];
    if ($user->create($data)) {
        header("Location: manage_users.php?success=1");
        exit();
    } else {
        $error = "Thêm người dùng thất bại.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm người dùng</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h1>Thêm người dùng</h1>
        <?php if (isset($error)): ?>
            <p class="text-danger"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label for="username">Tên người dùng</label>
                <input type="text" name="username" id="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="role">Vai trò</label>
                <select name="role" id="role" class="form-control" required>
                    <option value="customer">Khách hàng</option>
                    <option value="admin">Quản trị viên</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Thêm</button>
            <a href="manage_users.php" class="btn btn-secondary">Quay lại</a>
        </form>
    </div>
</body>
</html>
