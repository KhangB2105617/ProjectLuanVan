<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /unauthorized.php');
    exit;
}

require_once __DIR__ . '/../../src/bootstrap.php';
use NL\User;

$user = new User($PDO);

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: manage_users.php?error=missing_id');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($user->restore($id)) {
        header('Location: manage_users.php?success=restored');
        exit();
    } else {
        $error = "Khôi phục người dùng thất bại.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <title>Khôi phục người dùng</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
</head>
<body>
<div class="container mt-4">
    <h1>Khôi phục người dùng</h1>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <p>Bạn có chắc muốn khôi phục người dùng này không?</p>
    <form method="post">
        <button type="submit" class="btn btn-success">Khôi phục</button>
        <a href="manage_users.php" class="btn btn-secondary">Quay lại</a>
    </form>
</div>
</body>
</html>