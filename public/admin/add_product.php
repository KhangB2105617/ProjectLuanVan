<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use NL\Product;
use NL\Category;
use NL\Brand;

$product = new Product($PDO);
$categoryModel = new Category($PDO);
$brandModel = new Brand($PDO);

$categories = $categoryModel->getAll();
$brands = $brandModel->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ biểu mẫu
    $name = $_POST['name'];
    $price = $_POST['price'];
    $categoryId = $_POST['category_id'];
    $brandId = $_POST['brand_id'];
    $description = $_POST['description'];
    $quantity = $_POST['quantity'];

    $data = [
        ':name' => $name,
        ':price' => $price,
        ':category_id' => $categoryId,
        ':brand_id' => $brandId,
        ':description' => $description,
        ':quantity' => $quantity,
    ];

    // Xử lý hình ảnh
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image = $_FILES['image'];
        $imageName = uniqid() . '-' . basename($image['name']);
        $uploadDir = __DIR__ . '/../../public/assets/img/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $imagePath = $uploadDir . $imageName;
        if (move_uploaded_file($image['tmp_name'], $imagePath)) {
            $data[':image'] = $imageName;
        } else {
            $error = "Lỗi khi tải lên hình ảnh.";
        }
    } else {
        $data[':image'] = null;
        $error = "Vui lòng chọn một hình ảnh.";
    }

    // Lưu sản phẩm
    if (!isset($error) && $product->create($data)) {
        header("Location: manage_products.php?success=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm sản phẩm</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-4">
        <h1>Thêm sản phẩm</h1>
        <?php if (isset($error)): ?>
            <p class="text-danger"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Tên sản phẩm</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="price">Giá</label>
                <input type="number" name="price" id="price" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="quantity">Số lượng</label>
                <input type="number" name="quantity" id="quantity" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="category">Danh mục</label>
                <select name="category_id" id="category" class="form-control" required>
                    <option value="">Chọn danh mục</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category->id ?>"><?= htmlspecialchars($category->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="brand">Thương hiệu</label>
                <select name="brand_id" id="brand" class="form-control" required>
                    <option value="">Chọn thương hiệu</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?= $brand->id ?>"><?= htmlspecialchars($brand->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="image">Chọn hình ảnh</label>
                <input type="file" name="image" id="image" class="form-control" accept="image/*" required>
            </div>
            <div class="form-group">
                <label for="description">Mô tả</label>
                <textarea name="description" id="description" class="form-control" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Thêm</button>
            <a href="manage_products.php" class="btn btn-secondary">Quay lại</a>
        </form>

    </div>
</body>

</html>