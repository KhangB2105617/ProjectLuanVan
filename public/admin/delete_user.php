<?php
require_once __DIR__ . '/../../src/bootstrap.php';
use NL\User;

$user = new User($PDO);
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Soft delete: chỉ đánh dấu is_deleted = 1
    $stmt = $PDO->prepare("UPDATE users SET is_deleted = 1 WHERE id = :id");
    if ($stmt->execute([':id' => $id])) {
        header("Location: manage_users.php?success=1");
        exit();
    } else {
        $error = "Xóa người dùng thất bại.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xóa người dùng</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h1>Xóa người dùng</h1>
        <?php if (isset($error)): ?>
            <p class="text-danger"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <p>Bạn có chắc muốn xóa người dùng này không?</p>
        <form method="post">
            <button type="submit" class="btn btn-danger">Xóa</button>
            <a href="manage_users.php" class="btn btn-secondary">Quay lại</a>
        </form>
    </div>
</body>
</html>