<?php
require_once __DIR__ . '/../../src/bootstrap.php';
use NL\Product;

$product = new Product($PDO);
$id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($product->Softdelete($id)) {
        header("Location: manage_products.php?success=1");
        exit();
    } else {
        $error = "Xóa sản phẩm thất bại.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xóa sản phẩm</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h1>Xóa sản phẩm</h1>
        <?php if (isset($error)): ?>
            <p class="text-danger"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <p>Bạn có chắc muốn xóa sản phẩm này không?</p>
        <form method="post">
            <button type="submit" class="btn btn-danger">Xóa</button>
            <a href="manage_products.php" class="btn btn-secondary">Quay lại</a>
        </form>
    </div>
</body>
</html>
