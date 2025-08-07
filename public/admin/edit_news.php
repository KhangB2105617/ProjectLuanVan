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
    $title = $_POST['title'];
    $content = $_POST['content'];
    $imageName = $current->image;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image = $_FILES['image'];
        $imageName = uniqid() . '-' . basename($image['name']);
        $uploadDir = __DIR__ . '/../../public/assets/news/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        move_uploaded_file($image['tmp_name'], $uploadDir . $imageName);
    }

    $data = [
        ':id' => $id,
        ':title' => $title,
        ':content' => $content,
        ':image' => $imageName
    ];

    if ($news->update($data)) {
        header("Location: manage_news.php?updated=1");
        exit();
    } else {
        $error = "Cập nhật tin tức thất bại.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chỉnh sửa tin tức</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h2>Chỉnh sửa tin tức</h2>
    <?php if (isset($error)): ?>
        <p class="text-danger"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="title">Tiêu đề</label>
            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($current->title) ?>" required>
        </div>
        <div class="form-group">
            <label for="content">Nội dung</label>
            <textarea name="content" class="form-control" rows="6" required><?= htmlspecialchars($current->content) ?></textarea>
        </div>
        <div class="form-group">
            <label>Hình ảnh hiện tại</label><br>
            <?php if ($current->image): ?>
                <img src="/assets/news/<?= htmlspecialchars($current->image) ?>" alt="Ảnh" style="max-width: 200px;">
            <?php else: ?>
                <p>Không có ảnh</p>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <label for="image">Chọn ảnh mới (nếu có)</label>
            <input type="file" name="image" class="form-control" accept="image/*">
        </div>
        <button type="submit" class="btn btn-success">Lưu thay đổi</button>
        <a href="manage_news.php" class="btn btn-secondary">Quay lại</a>
    </form>
</div>
</body>
</html>