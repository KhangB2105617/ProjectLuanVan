<?php
require_once __DIR__ . '/../../src/bootstrap.php';

$id = $_GET['id'];
$stmt = $PDO->prepare("SELECT * FROM discount_codes WHERE id = :id");
$stmt->execute([':id' => $id]);
$discount = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$discount) {
    echo "Không tìm thấy mã!";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $PDO->prepare("UPDATE discount_codes 
        SET code = :code, discount_type = :type, discount_value = :value, 
            max_usage = :max, expired_at = :expired 
        WHERE id = :id");

    $stmt->execute([
        ':code' => $_POST['code'],
        ':type' => $_POST['discount_type'],
        ':value' => $_POST['discount_value'],
        ':max' => $_POST['max_usage'],
        ':expired' => $_POST['expired_at'],
        ':id' => $id,
    ]);

    header("Location: manage_discounts.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa mã khuyến mãi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Sửa mã khuyến mãi</h2>
    <form method="POST">
        <div class="mb-3">
            <label>Mã khuyến mãi</label>
            <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($discount['code']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Loại giảm giá</label>
            <select name="discount_type" class="form-control" required>
                <option value="percent" <?= $discount['discount_type'] === 'percent' ? 'selected' : '' ?>>Phần trăm</option>
                <option value="fixed" <?= $discount['discount_type'] === 'fixed' ? 'selected' : '' ?>>Số tiền</option>
            </select>
        </div>
        <div class="mb-3">
            <label>Giá trị giảm</label>
            <input type="number" name="discount_value" class="form-control" value="<?= $discount['discount_value'] ?>" required>
        </div>
        <div class="mb-3">
            <label>Số lần sử dụng tối đa</label>
            <input type="number" name="max_usage" class="form-control" value="<?= $discount['max_usage'] ?>">
        </div>
        <div class="mb-3">
            <label>Ngày hết hạn</label>
            <input type="datetime-local" name="expired_at" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($discount['expired_at'])) ?>">
        </div>
        <button type="submit" class="btn btn-primary">Lưu</button>
        <a href="manage_discounts.php" class="btn btn-secondary">Hủy</a>
    </form>
</div>
</body>
</html>
