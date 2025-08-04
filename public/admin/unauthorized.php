<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Không có quyền truy cập</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex justify-content-center align-items-center" style="height: 100vh;">

    <div class="text-center">
        <i class="fas fa-lock fa-4x text-danger mb-3"></i>
        <h2 class="text-danger">403 - Không có quyền truy cập</h2>
        <p class="lead">Bạn không có quyền để xem trang này.</p>
        <?php if (isset($_SESSION['role'])): ?>
            <a href="/index.php" class="btn btn-primary mt-3"><i class="fas fa-home"></i> Quay về trang chủ</a>
        <?php else: ?>
            <a href="/login.php" class="btn btn-warning mt-3"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a>
        <?php endif; ?>
    </div>

</body>
</html>