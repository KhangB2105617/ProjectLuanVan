<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../src/bootstrap.php';

use NL\Order;

$order = new Order($PDO);
$year = $_GET['year'] ?? date('Y');
$reportData = $order->getMonthlySalesReportByYear($year);

// Dữ liệu để truyền vào Chart.js
$labels = [];
$salesData = [];
$orderData = [];
$productData = [];

foreach ($reportData as $item) {
    $labels[] = 'Tháng ' . $item->month;
    $salesData[] = $item->total_sales;
    $orderData[] = $item->total_orders;
    $productData[] = $item->total_products;
}
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : null;
$selectedYear  = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

$order = new Order($PDO);

if ($selectedMonth) {
    // Nếu người dùng chọn tháng => Lấy báo cáo theo tháng và năm
    $reportData = $order->getSalesReportByMonthYear($selectedMonth, $selectedYear);
} else {
    // Mặc định hoặc chỉ chọn năm => Lấy báo cáo cả 12 tháng của năm
    $reportData = $order->getMonthlySalesReportByYear($selectedYear);
}
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
                    <h1>Thống Kê</h1>
                    <form method="GET" class="row g-2 mb-3">

                        <div class="col-auto">
                            <select name="month" class="form-select">
                                <option value="">-- Chọn tháng --</option>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m ?>" <?= (isset($_GET['month']) && $_GET['month'] == $m) ? 'selected' : '' ?>>
                                        Tháng <?= $m ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="col-auto">
                            <select name="year" class="form-select">
                                <option value="">-- Chọn năm --</option>
                                <?php
                                $currentYear = date('Y');
                                for ($y = $currentYear; $y >= $currentYear - 5; $y--): ?>
                                    <option value="<?= $y ?>" <?= (isset($_GET['year']) && $_GET['year'] == $y) ? 'selected' : '' ?>>
                                        Năm <?= $y ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Thống Kê</button>
                        </div>
                    </form>
<?php if (isset($_GET['month']) && isset($_GET['year']) && $_GET['month'] !== '' && $_GET['year'] !== ''): ?>
    <table class="table table-bordered mt-3">
        <thead>
            <tr>
                <th>Thời gian</th>
                <th>Tổng doanh thu (VND)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reportData as $item): ?>
                <tr>
                    <td>
                        <?php if (isset($item->month)): ?>
                            Tháng <?= $item->month ?>
                        <?php else: ?>
                            <?= $selectedMonth ?>/<?= $selectedYear ?>
                        <?php endif; ?>
                    </td>
                    <td><?= number_format($item->total_sales ?? 0, 0, ',', '.') ?> đ</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Biểu đồ doanh thu -->
    <h5 class="mt-5">Biểu đồ Doanh thu</h5>
    <canvas id="salesChart" height="100"></canvas>

    <!-- Biểu đồ đơn hàng -->
    <h5 class="mt-5">Biểu đồ Số đơn hàng</h5>
    <canvas id="ordersChart" height="100"></canvas>

    <!-- Biểu đồ sản phẩm bán ra -->
    <h5 class="mt-5">Biểu đồ Sản phẩm bán ra</h5>
    <canvas id="productsChart" height="100"></canvas>
<?php endif; ?>
            </main>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
            <script>
                const labels = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
                const salesData = <?= json_encode($salesData) ?>;
                const ordersData = <?= json_encode($orderData) ?>;
                const productsData = <?= json_encode($productData) ?>;

                // Biểu đồ doanh thu
                new Chart(document.getElementById('salesChart'), {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Doanh thu (VND)',
                            data: salesData,
                            backgroundColor: 'rgba(54, 162, 235, 0.6)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        plugins: {
                            title: {
                                display: true,
                                text: 'Biểu đồ Doanh thu'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });

                // Biểu đồ đơn hàng
                new Chart(document.getElementById('ordersChart'), {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Số đơn hàng',
                            data: ordersData,
                            backgroundColor: 'rgba(255, 159, 64, 0.6)',
                            borderColor: 'rgba(255, 159, 64, 1)',
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: {
                        plugins: {
                            title: {
                                display: true,
                                text: 'Biểu đồ Đơn hàng'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                precision: 0
                            }
                        }
                    }
                });

                // Biểu đồ sản phẩm bán ra
                new Chart(document.getElementById('productsChart'), {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Sản phẩm bán ra',
                            data: productsData,
                            backgroundColor: 'rgba(75, 192, 192, 0.6)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        plugins: {
                            title: {
                                display: true,
                                text: 'Biểu đồ Sản phẩm bán ra'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                precision: 0
                            }
                        }
                    }
                });
            </script>

</body>

</html>