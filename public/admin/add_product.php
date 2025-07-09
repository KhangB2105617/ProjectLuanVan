<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use NL\Product;

$product = new Product($PDO);

$queryCategories = "SELECT DISTINCT category FROM products";
$stmtCategories = $PDO->prepare($queryCategories);
$stmtCategories->execute();
$categories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);

$queryBrands = "SELECT DISTINCT brand FROM products";
$stmtBrands = $PDO->prepare($queryBrands);
$stmtBrands->execute();
$brands = $stmtBrands->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ biểu mẫu
    $name = $_POST['name'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $brand = $_POST['brand'];
    $description = $_POST['description'];
    $quantity = $_POST['quantity'];

    $data = [
        ':name' => $name,
        ':price' => $price,
        ':category' => $category,
        ':brand' => $brand,
        ':description' => $description,
        ':quantity' => $quantity,
    ];

    // Xử lý hình ảnh
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image = $_FILES['image'];

        // Tạo tên tệp mới để tránh trùng lặp
        $imageName = uniqid() . '-' . basename($image['name']);
        $uploadDir = __DIR__ . '/../../public/assets/img/';  // Đảm bảo lưu trong thư mục img

        // Tạo thư mục nếu chưa tồn tại
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Di chuyển tệp từ thư mục tạm thời đến thư mục đích
        $imagePath = $uploadDir . $imageName;
        if (move_uploaded_file($image['tmp_name'], $imagePath)) {
            // Lưu đường dẫn hình ảnh vào cơ sở dữ liệu
            $data[':image'] = '' . $imageName;
        } else {
            $error = "Lỗi khi tải lên hình ảnh.";
        }
    } else {
        // Nếu không có hình ảnh, để giá trị là null
        $data[':image'] = null;
        $error = "Vui lòng chọn một hình ảnh.";
    }

    // Lưu sản phẩm vào cơ sở dữ liệu
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
                <select name="category" id="category" class="form-control" required>
                    <option value="">Chọn danh mục</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= htmlspecialchars($category['category']) ?>"><?= htmlspecialchars($category['category']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="brand">Thương hiệu</label>
                <select name="brand" id="brand" class="form-control" required>
                    <option value="">Chọn thương hiệu</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?= htmlspecialchars($brand['brand']) ?>"><?= htmlspecialchars($brand['brand']) ?></option>
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