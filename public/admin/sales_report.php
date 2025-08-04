<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../src/bootstrap.php';

use NL\Order;

$order = new Order($PDO);

$from = $_GET['from'] ?? null;
$to   = $_GET['to'] ?? null;

$from = ($from !== '') ? $from : null;
$to   = ($to !== '') ? $to : null;

$type = $_GET['type'] ?? 'month';

if ($from && $to && $from > $to) {
    echo '<div class="alert alert-danger">Lỗi: Vui lòng chọn lại ngày tháng năm cần lọc phù hợp.</div>';
}

$salesData = $order->getTotalSalesReport($type, $from, $to);
$topProducts = $order->getTopSellingProducts(5);
$leastProducts = $order->getLeastSellingProducts(5);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
        content="Cửa hàng Classic cung cấp các sản phẩm thiết bị văn phòng chất lượng cao với dịch vụ khách hàng tốt nhất.">
    <meta name="keywords" content="thiết bị văn phòng, máy in, máy quét, sản phẩm văn phòng">
    <title>Báo cáo doanh số bán hàng</title>
    <link rel="icon" href="assets/img/vector-shop-icon-png_302739.jpg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4">
            <div class="pt-3">
                <h1>Báo cáo doanh thu</h1>
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-auto">
                        <input type="date" name="from" class="form-control" value="<?= $_GET['from'] ?? '' ?>" placeholder="Từ ngày">
                    </div>
                    <div class="col-auto">
                        <input type="date" name="to" class="form-control" value="<?= $_GET['to'] ?? '' ?>" placeholder="Đến ngày">
                    </div>
                    <div class="col-auto">
                        <select name="type" class="form-select">
                            <option value="day" <?= $type === 'day' ? 'selected' : '' ?>>Theo ngày</option>
                            <option value="month" <?= $type === 'month' ? 'selected' : '' ?>>Theo tháng</option>
                            <option value="quarter" <?= $type === 'quarter' ? 'selected' : '' ?>>Theo quý</option>
                            <option value="year" <?= $type === 'year' ? 'selected' : '' ?>>Theo năm</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Thống Kê</button>
                    </div>
                </form>

                <table class="table table-bordered mt-3">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Tổng doanh thu (VND)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salesData as $data): ?>
                            <tr>
                                <td><?= htmlspecialchars($data->time_period) ?></td>
                                <td><?= number_format($data->total_sales, 0, ',', '.') ?> đ</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h2 class="mt-5">Biểu đồ doanh thu</h2>
                <canvas id="salesChart" height="100"></canvas>

                <h2 class="mt-5">Top sản phẩm bán chạy nhất</h2>
                <table class="table table-bordered table-hover">
                    <thead class="table-success">
                        <tr>
                            <th>STT</th>
                            <th>Tên sản phẩm</th>
                            <th>Tổng số lượng bán</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topProducts as $index => $product): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($product->product_name) ?></td>
                                <td><?= $product->total_quantity ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h2 class="mt-3">Biểu đồ Top sản phẩm bán chạy</h2>
                <canvas id="topProductsChart" height="100"></canvas>

                <h2 class="mt-5">Top sản phẩm bán ít nhất</h2>
                <table class="table table-bordered table-hover">
                    <thead class="table-danger">
                        <tr>
                            <th>#</th>
                            <th>Tên sản phẩm</th>
                            <th>Tổng số lượng bán</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leastProducts as $index => $product): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($product->product_name) ?></td>
                                <td><?= $product->total_quantity ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<script>
    const salesLabels = <?= json_encode(array_map(fn($d) => $d->time_period, $salesData)) ?>;
    const salesData = <?= json_encode(array_map(fn($d) => $d->total_sales, $salesData)) ?>;

    const topLabels = <?= json_encode(array_map(fn($p) => $p->product_name, $topProducts)) ?>;
    const topData = <?= json_encode(array_map(fn($p) => $p->total_quantity, $topProducts)) ?>;

    new Chart(document.getElementById('salesChart'), {
        type: 'bar',
        data: {
            labels: salesLabels,
            datasets: [{
                label: 'Tổng doanh thu',
                data: salesData,
                borderColor: 'blue',
                backgroundColor: 'lightblue',
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top'
                }
            }
        }
    });

    new Chart(document.getElementById('topProductsChart'), {
        type: 'bar',
        data: {
            labels: topLabels,
            datasets: [{
                label: 'Số lượng bán',
                data: topData,
                backgroundColor: 'green'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
