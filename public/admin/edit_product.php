<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use NL\Product;

$product = new Product($PDO);
$id = $_GET['id'];
$productData = $product->getById($id);

$queryCategories = "SELECT id, name FROM categories";
$stmtCategories = $PDO->prepare($queryCategories);
$stmtCategories->execute();
$categories = $stmtCategories->fetchAll(PDO::FETCH_ASSOC);

$queryBrands = "SELECT id, name FROM brands";
$stmtBrands = $PDO->prepare($queryBrands);
$stmtBrands->execute();
$brands = $stmtBrands->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy thông tin từ form
    $data = [
        ':name' => $_POST['name'],
        ':price' => $_POST['price'],
        ':category_id' => $_POST['category_id'],
        ':brand_id' => $_POST['brand_id'],
        ':description' => $_POST['description'],
        ':quantity' => $_POST['quantity'],
    ];

    // Xử lý tệp hình ảnh
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image = $_FILES['image'];

        // Tạo tên tệp hình ảnh mới để tránh trùng lặp
        $imageName = uniqid() . '-' . basename($image['name']);
        $uploadDir = __DIR__ . '/../../public/assets/img/';

        // Kiểm tra thư mục nếu chưa có thì tạo mới
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true); // Tạo thư mục nếu chưa tồn tại
        }

        // Di chuyển tệp hình ảnh từ tạm thời đến thư mục lưu trữ
        $imagePath = $uploadDir . $imageName;
        if (move_uploaded_file($image['tmp_name'], $imagePath)) {
            // Nếu upload thành công, cập nhật đường dẫn vào cơ sở dữ liệu
            $data[':image'] = '' . $imageName; // Lưu đường dẫn hình ảnh trong cơ sở dữ liệu
        } else {
            $error = "Lỗi khi tải lên hình ảnh.";
        }
    } else {
        // Nếu không có hình ảnh mới, giữ lại hình ảnh cũ
        $data[':image'] = $productData->image;
    }

    // Cập nhật thông tin sản phẩm trong cơ sở dữ liệu
    if ($product->update($id, $data)) {
        header("Location: manage_products.php?success=1");
        exit();
    } else {
        $error = "Cập nhật sản phẩm thất bại.";
    }
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa sản phẩm</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-4">
        <h1>Sửa sản phẩm</h1>
        <?php if (isset($error)): ?>
            <p class="text-danger"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Tên sản phẩm</label>
                <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($productData->name) ?>" required>
            </div>
            <div class="form-group">
                <label for="price">Giá</label>
                <input type="number" name="price" id="price" class="form-control" value="<?= htmlspecialchars($productData->price) ?>" required>
            </div>
            <div class="form-group">
                <label for="quantity">Số lượng</label>
                <input type="number" name="quantity" id="quantity" class="form-control" value="<?= htmlspecialchars($productData->quantity) ?>" required>
            </div>
            <div class="form-group">
                <label for="category">Danh mục</label>
                <select name="category_id" id="category" class="form-control" required>
                    <option value="">Chọn danh mục</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" <?= ($category['id'] == $productData->category_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="brand">Thương hiệu</label>
                <select name="brand_id" id="brand" class="form-control" required>
                    <option value="">Chọn thương hiệu</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?= $brand['id'] ?>" <?= ($brand['id'] == $productData->brand_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($brand['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

            </div>

            <div class="form-group">
                <label for="image">Chọn hình ảnh</label>
                <input type="file" name="image" id="image" class="form-control">
            </div>
            <div class="form-group">
                <label for="description">Mô tả</label>
                <textarea name="description" id="description" class="form-control" required><?= htmlspecialchars($productData->description) ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Cập nhật</button>
            <a href="manage_products.php" class="btn btn-secondary">Quay lại</a>
        </form>