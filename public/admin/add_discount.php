<?php
require_once __DIR__ . '/../../src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $PDO->prepare("INSERT INTO discount_codes 
        (code, discount_type, discount_value, max_usage, start_at, expired_at) 
        VALUES (:code, :type, :value, :max, :start, :expired)");

    $stmt->execute([
        ':code'    => $_POST['code'],
        ':type'    => $_POST['discount_type'],
        ':value'   => $_POST['discount_value'],
        ':max'     => $_POST['max_usage'],
        ':start'   => $_POST['start_at'],
        ':expired' => $_POST['expired_at'],
    ]);

    header("Location: manage_discounts.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm mã khuyến mãi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>
    <div class="container mt-4">
        <h2>Thêm mã khuyến mãi</h2>
        <form method="POST">
            <div class="mb-3">
                <label>Mã khuyến mãi</label>
                <input type="text" name="code" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Loại giảm giá</label>
                <select name="discount_type" class="form-control" required>
                    <option value="percent">Phần trăm</option>
                    <option value="fixed">Số tiền</option>
                </select>
            </div>
            <div class="mb-3">
                <label>Giá trị giảm</label>
                <input type="number" name="discount_value" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Số lần sử dụng tối đa</label>
                <input type="number" name="max_usage" class="form-control" value="1">
            </div>
            <div class="mb-3">
                <label>Ngày bắt đầu</label>
                <input type="text" name="start_at" id="start_at" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Ngày hết hạn</label>
                <input type="text" name="expired_at" id="expired_at" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Thêm</button>
            <a href="manage_discounts.php" class="btn btn-secondary">Hủy</a>
        </form>
    </div>

    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr("#start_at", {
            enableTime: true,
            dateFormat: "Y-m-d H:i:S",
            minDate: "today"
        });

        flatpickr("#expired_at", {
            enableTime: true,
            dateFormat: "Y-m-d H:i:S",
            minDate: "today"
        });
    </script>
</body>
</html>