<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use NL\ProductNews;
$news = new ProductNews($PDO);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $imageName = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image = $_FILES['image'];
        $imageName = uniqid() . '-' . basename($image['name']);
        $uploadDir = __DIR__ . '/../../public/assets/news/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        move_uploaded_file($image['tmp_name'], $uploadDir . $imageName);
    }

    $data = [
        ':title' => $title,
        ':content' => $content,
        ':image' => $imageName
    ];

    if ($news->create($data)) {
        header("Location: manage_news.php?success=1");
        exit();
    } else {
        $error = "Thêm tin tức thất bại.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm tin tức</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h2>Thêm tin tức</h2>
    <?php if (isset($error)): ?>
        <p class="text-danger"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="title">Tiêu đề</label>
            <input type="text" name="title" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="content">Nội dung</label>
            <textarea name="content" class="form-control" rows="6" required></textarea>
        </div>
        <div class="form-group">
            <label for="image">Hình ảnh</label>
            <input type="file" name="image" class="form-control" accept="image/*">
        </div>
        <button type="submit" class="btn btn-primary">Thêm</button>
        <a href="manage_news.php" class="btn btn-secondary">Quay lại</a>
    </form>
</div>
</body>
</html>