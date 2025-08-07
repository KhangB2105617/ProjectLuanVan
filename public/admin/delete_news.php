<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use NL\ProductNews;

$news = new ProductNews($PDO);

$id = $_GET['id'] ?? null;
$current = $news->getById($id);

if (!$current) {
    die("Tin tức không tồn tại.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($news->delete($id)) {
        // Xóa ảnh nếu có
        if ($current->image) {
            $path = __DIR__ . '/../../public/assets/news/' . $current->image;
            if (file_exists($path)) {
                unlink($path);
            }
        }
        header("Location: manage_news.php?deleted=1");
        exit();
    } else {
        $error = "Xóa thất bại.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xóa tin tức</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h2>Xóa tin tức</h2>
    <?php if (isset($error)): ?>
        <p class="text-danger"><?= htmlspecialchars($error) ?></p>
    <?php else: ?>
        <p>Bạn có chắc chắn muốn xóa tin tức: <strong><?= htmlspecialchars($current->title) ?></strong> không?</p>
        <form method="post">
            <button type="submit" class="btn btn-danger">Xóa</button>
            <a href="manage_news.php" class="btn btn-secondary">Hủy</a>
        </form>
    <?php endif; ?>
</div>
</body>
</html>