<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../src/bootstrap.php';

use NL\Order;

$order = new Order($PDO);
function groupOrders($orders)
{
    $grouped = [];
    foreach ($orders as $row) {
        $grouped[$row->id]['info'] = $row;
        $grouped[$row->id]['items'][] = [
            'product_name' => $row->product_name,
            'quantity' => $row->quantity,
            'price' => $row->price,
        ];
    }
    return $grouped;
}
// Lấy tất cả đơn đặt hàng, bao gồm email, địa chỉ và số điện thoại
$filters = [
    'status' => $_GET['status'] ?? '',
    'payment_method' => $_GET['payment_method'] ?? '',
    'from' => $_GET['from'] ?? '',
    'to' => $_GET['to'] ?? '',
];

$orders = $order->getFilteredOrders($filters);
$groupedOrders = groupOrders($orders);
$error = '';
$success = '';

// Nếu người dùng gửi form để cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['status'];

    // Cập nhật trạng thái đơn hàng
    if ($order->updateOrderStatus($orderId, $newStatus)) {
        // Nếu cập nhật thành công
        $success = "Cập nhật trạng thái đơn hàng thành công!";
    } else {
        // Nếu cập nhật thất bại
        $error = "Cập nhật trạng thái vận chuyển thất bại.";
    }

    // Tự động làm mới danh sách đơn hàng sau khi cập nhật
    $orders = $order->getAllOrders(); // Lấy lại danh sách đơn hàng mới nhất
    $groupedOrders = groupOrders($orders);
}

// Nếu người dùng gửi yêu cầu xóa đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order_id'])) {
    $deleteOrderId = $_POST['delete_order_id'];

    // Xóa đơn hàng
    if ($order->deleteOrder($deleteOrderId)) {
        // Nếu xóa thành công
        $success = "Đơn hàng đã được xóa thành công!";
    } else {
        // Nếu xóa thất bại
        $error = "Xóa đơn hàng thất bại.";
    }

    // Tự động làm mới danh sách đơn hàng sau khi xóa
    $orders = $order->getAllOrders(); // Lấy lại danh sách đơn hàng mới nhất
    $groupedOrders = groupOrders($orders);
}
// Xử lý duyệt hoặc từ chối yêu cầu hủy
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order_id'])) {
    $orderId = $_POST['cancel_order_id'];
    $approve = null;

    if (isset($_POST['approve_cancel'])) {
        $approve = 1; // Duyệt hủy
    } elseif (isset($_POST['deny_cancel'])) {
        $approve = 0; // Từ chối hủy
    }

    if (!is_null($approve)) {
        $stmt = $PDO->prepare("UPDATE orders SET cancel_approved = :approve WHERE id = :id");
        $stmt->execute([
            'approve' => $approve,
            'id' => $orderId
        ]);

        $success = ($approve === 1) ? "Đã duyệt yêu cầu hủy đơn hàng." : "Đã từ chối yêu cầu hủy đơn hàng.";

        // Tải lại danh sách đơn hàng mới
        $orders = $order->getAllOrders();
        $groupedOrders = groupOrders($orders);
    } else {
        $error = "Yêu cầu không hợp lệ.";
    }
}
$pageTitle = "Quản lý vận chuyển đơn hàng";
include 'includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?> <!-- ✅ Sidebar dùng chung -->

        <main class="col-md-10 ms-sm-auto px-md-4" style="margin-left: 17%;">
            <div class="pt-4">
                <h1><?= $pageTitle ?></h1>
                <form method="get" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label for="status" class="form-label">Trạng thái đơn hàng</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">-- Tất cả --</option>
                            <option value="Đang xử lý" <?= ($_GET['status'] ?? '') == 'Đang xử lý' ? 'selected' : '' ?>>Đang xử lý</option>
                            <option value="Đang vận chuyển" <?= ($_GET['status'] ?? '') == 'Đang vận chuyển' ? 'selected' : '' ?>>Đang vận chuyển</option>
                            <option value="Đã giao" <?= ($_GET['status'] ?? '') == 'Đã giao' ? 'selected' : '' ?>>Đã giao</option>
                            <option value="Đã hủy" <?= ($_GET['status'] ?? '') == 'Đã hủy' ? 'selected' : '' ?>>Đã hủy</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="payment_method" class="form-label">Phương thức thanh toán</label>
                        <select name="payment_method" id="payment_method" class="form-select">
                            <option value="">-- Tất cả --</option>
                            <option value="cod" <?= ($_GET['payment_method'] ?? '') == 'cod' ? 'selected' : '' ?>>Thanh toán khi nhận</option>
                            <option value="e_wallet" <?= ($_GET['payment_method'] ?? '') == 'e_wallet' ? 'selected' : '' ?>>Thanh toán VNPay</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="from" class="form-label">Từ ngày</label>
                        <input type="date" name="from" id="from" value="<?= htmlspecialchars($_GET['from'] ?? '') ?>" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label for="to" class="form-label">Đến ngày</label>
                        <input type="date" name="to" id="to" value="<?= htmlspecialchars($_GET['to'] ?? '') ?>" class="form-control">
                    </div>
                    <div class="col-md-12 mt-2">
                        <button type="submit" class="btn btn-primary">Lọc đơn hàng</button>
                        <a href="manage_shipping.php" class="btn btn-secondary">Hủy lọc</a>
                    </div>
                </form>

                <?php if (!empty($error)): ?>
                    <div id="alert-box" class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div id="alert-box" class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID Đơn hàng</th>
                            <th>Tên khách hàng</th>
                            <th>Địa chỉ</th>
                            <th>Số điện thoại</th>
                            <th>Sản phẩm</th>
                            <th>Số lượng</th>
                            <th>Giá</th>
                            <th>Phương thức</th>
                            <th>Ngày đặt</th>
                            <th>Trạng thái</th>
                            <th>Xóa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groupedOrders as $orderId => $data): ?>
                            <tr>
                                <td><?= htmlspecialchars($orderId) ?></td>
                                <td><?= htmlspecialchars($data['info']->customer_name) ?></td>
                                <td><?= htmlspecialchars($data['info']->customer_address) ?></td>
                                <td><?= htmlspecialchars($data['info']->customer_phone) ?></td>
                                <td>
                                    <ul class="list-unstyled">
                                        <?php foreach ($data['items'] as $item): ?>
                                            <li><?= htmlspecialchars($item['product_name']) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td>
                                    <ul class="list-unstyled">
                                        <?php foreach ($data['items'] as $item): ?>
                                            <li><?= htmlspecialchars($item['quantity']) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td>
                                    <ul class="list-unstyled">
                                        <?php foreach ($data['items'] as $item): ?>
                                            <li><?= number_format($item['price']) ?>đ</li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td><?= htmlspecialchars($data['info']->payment_method === 'cod' ? 'COD' : 'VNPay') ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($data['info']->created_at)) ?></td>

                                <td>
                                    <?php
                                    $status = $data['info']->status;
                                    $cancelApproved = $data['info']->cancel_approved;
                                    if ($cancelApproved === 1) {
                                        $selectStyle = 'background-color: #dc3545; color: #fff;'; // đỏ
                                    } else {
                                        $statusColors = [
                                            'Đang xử lý' => 'background-color: #ffc107; color: #000;',
                                            'Đang vận chuyển' => 'background-color: #17a2b8; color: #fff;',
                                            'Đã giao' => 'background-color: #28a745; color: #fff;',
                                        ];
                                        $selectStyle = $statusColors[$status] ?? 'background-color: #6c757d; color: #fff;';
                                    }
                                    ?>


                                    <?php if ($cancelApproved === 1): ?>
                                        <!-- Đã hủy: hiển thị giống dropdown nhưng không cho chỉnh -->
                                        <span class="d-inline-block text-center rounded-pill shadow-sm px-3 py-1"
                                            style="min-width: 180px; <?= $selectStyle ?>">Đã hủy
                                        </span>
                                    <?php else: ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="order_id" value="<?= htmlspecialchars($data['info']->id) ?>">
                                            <select name="status"
                                                class="form-select form-select-sm fw-bold text-center rounded-pill shadow-sm border-0"
                                                style="min-width: 180px; <?= $selectStyle ?>"
                                                onchange="this.form.submit()">
                                                <option value="Đang xử lý" <?= $status == 'Đang xử lý' ? 'selected' : '' ?>>
                                                    🕒 Đang xử lý
                                                </option>
                                                <option value="Đang vận chuyển" <?= $status == 'Đang vận chuyển' ? 'selected' : '' ?>>
                                                    🚚 Đang vận chuyển
                                                </option>
                                                <option value="Đã giao" <?= $status == 'Đã giao' ? 'selected' : '' ?>>
                                                    ✅ Đã giao
                                                </option>
                                            </select>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($data['info']->cancel_request == 1 && is_null($data['info']->cancel_approved)): ?>
                                        <form method="post" class="mt-2">
                                            <input type="hidden" name="cancel_order_id" value="<?= htmlspecialchars($data['info']->id) ?>">
                                            <button type="submit" name="approve_cancel" class="btn btn-danger btn-sm">Duyệt hủy</button>
                                            <button type="submit" name="deny_cancel" class="btn btn-warning btn-sm">Từ chối hủy</button>
                                        </form>
                                    <?php elseif ($data['info']->cancel_approved === 1): ?>
                                        <p class="text-success mt-2">Đã được duyệt hủy</p>
                                    <?php elseif ($data['info']->cancel_approved === 0): ?>
                                        <p class="text-danger mt-2">Yêu cầu hủy bị từ chối</p>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="delete_order_id" value="<?= htmlspecialchars($data['info']->id) ?>">
                                        <button type="submit" class="btn btn-danger btn-sm mt-2">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>
            <script>
                // Tự động ẩn alert sau 2 giây
                setTimeout(() => {
                    const alertBox = document.getElementById('alert-box');
                    if (alertBox) {
                        // Bootstrap hỗ trợ class 'fade' và 'show', chỉ cần loại 'show' là sẽ mờ dần rồi biến mất
                        alertBox.classList.remove('show');
                    }
                }, 1500);
            </script>
            <!-- Bootstrap JS (optional) -->
            <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
            <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
            </body>

            </html>