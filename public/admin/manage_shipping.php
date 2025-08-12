<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../src/bootstrap.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header('Location: /unauthorized.php');
    exit;
}

use NL\Order;
use NL\Stock;

$order = new Order($PDO);
$stockModel = new Stock($PDO);
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
        if ($newStatus === 'Đang xử lý') {
            // Lấy chi tiết sản phẩm trong đơn
            $orderItems = $order->getOrderItems($orderId);
            foreach ($orderItems as $item) {
                // Trừ tồn kho từng sản phẩm
                $stockModel->updateStockQuantity($item['product_id'], $item['quantity'], 'out', null, null, null, false);
            }
        }
        $success = "Cập nhật trạng thái đơn hàng thành công!";
    } else {
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
        if ($approve === 1) {
            // Lấy trạng thái đơn hàng hiện tại
            $stmtStatus = $PDO->prepare("SELECT status FROM orders WHERE id = :id");
            $stmtStatus->execute(['id' => $orderId]);
            $orderStatus = $stmtStatus->fetchColumn();

            // Duyệt hủy đơn hàng
            $stmt = $PDO->prepare("UPDATE orders SET cancel_approved = :approve WHERE id = :id");
            $stmt->execute([
                'approve' => $approve,
                'id' => $orderId
            ]);

            // Lấy các sản phẩm và số lượng trong đơn hàng
            $stmtItems = $PDO->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = :order_id");
            $stmtItems->execute(['order_id' => $orderId]);
            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            // Chỉ cộng lại kho nếu đơn đã trừ kho trước đó
            if ($orderStatus === 'Đang xử lý') {
                foreach ($items as $item) {
                    $stockModel->updateStockQuantity(
                        $item['product_id'],
                        $item['quantity'],
                        'in',      // nhập kho
                        null,      // import_price
                        null,      // export_price
                        null,      // user_id
                        false      // không ghi log
                    );
                }
            }

            $success = "Đã duyệt yêu cầu hủy đơn hàng" .
                (in_array($orderStatus, ['Đang xử lý', 'Đang giao']) ? " và cập nhật tồn kho." : ".");
        }
    } else {
        // Từ chối hủy đơn hàng
        $stmt = $PDO->prepare("UPDATE orders SET cancel_approved = :approve WHERE id = :id");
        $stmt->execute([
            'approve' => $approve,
            'id' => $orderId
        ]);
        $success = "Đã từ chối yêu cầu hủy đơn hàng.";
    }

    // Tải lại danh sách đơn hàng mới nhất
    $orders = $order->getAllOrders();
    $groupedOrders = groupOrders($orders);
}

// Thêm hàm lấy trạng thái thanh toán VNPay cho 1 order_id
function getVNPayStatus(PDO $pdo, int $orderId): ?string
{
    $stmt = $pdo->prepare("SELECT status FROM vnpay_transactions WHERE order_id = :order_id ORDER BY id DESC LIMIT 1");
    $stmt->execute(['order_id' => $orderId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['status'] ?? null; // Có thể là 'success', 'pending', ...
}
$pageTitle = "Quản lý vận chuyển đơn hàng";
include 'includes/header.php';
?>
<style>
    /* Gạch chân phân cách giữa các dòng đơn hàng */
    #productsTable tbody tr td {
        border-bottom: 1px solid #bbbdc0ff !important;
        /* Màu xanh bạn có thể thay đổi */
    }

    /* Bỏ gạch chân dòng cuối cùng */
    #productsTable tbody tr:last-child td {
        border-bottom: none !important;
    }

    .list-unstyled {
        margin: 0;
        padding-left: 0;
    }

    #productsTable tbody td ul li {
        white-space: nowrap;
        /* Không xuống dòng, nếu tên sản phẩm dài sẽ bị cắt hoặc tràn */
        overflow: hidden;
        text-overflow: ellipsis;
        /* Hiện dấu ... nếu dài quá */
        max-width: 100px;
        /* hoặc tùy chỉnh độ rộng phù hợp */
    }
</style>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?> <!-- ✅ Sidebar dùng chung -->

        <main class="col-md-10 ms-sm-auto px-0" style="margin-left: 17%;">
            <div class="pt-4">
                <h1><?= $pageTitle ?></h1>
                <form method="get" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label for="status" class="form-label">Trạng thái đơn hàng</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">-- Tất cả --</option>
                            <option value="Chờ xử lý" <?= ($_GET['status'] ?? '') == 'Chờ xử lý' ? 'selected' : '' ?>>Chờ xử lý</option>
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

                <!-- Toast container -->
                <div aria-live="polite" aria-atomic="true" class="position-relative" style="z-index: 1080;">
                    <div class="toast-container position-fixed top-0 end-0 p-3">
                        <?php if (!empty($success)): ?>
                            <div id="successToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                                <div class="d-flex">
                                    <div class="toast-body">
                                        <?= htmlspecialchars($success) ?>
                                    </div>
                                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                            <div id="errorToast" class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
                                <div class="d-flex">
                                    <div class="toast-body">
                                        <?= htmlspecialchars($error) ?>
                                    </div>
                                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>


                <table id="productsTable" class="table table-bordered">
                    <thead class="table-primary">
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
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groupedOrders as $orderId => $data): ?>
                            <tr>
                                <td><?= htmlspecialchars($orderId) ?></td>
                                <td><?= htmlspecialchars($data['info']->customer_name) ?></td>
                                <td><?= htmlspecialchars($data['info']->customer_address) ?></td>
                                <td><?= htmlspecialchars($data['info']->customer_phone) ?></td>
                                <?php
                                $productNames = array_map(fn($item) => $item['product_name'], $data['items']);
                                $title = implode(', ', $productNames);
                                ?>
                                <td>
                                    <ul class="list-unstyled">
                                        <?php foreach ($data['items'] as $item): ?>
                                            <li title="<?= htmlspecialchars($item['product_name']) ?>">- <?= htmlspecialchars($item['product_name']) ?></li>
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
                                <td><?= number_format($data['info']->total_price) ?>đ</td>

                                <td>
                                    <?php
                                    $paymentMethod = $data['info']->payment_method;
                                    $orderStatus = $data['info']->status;

                                    if ($paymentMethod === 'cod') {
                                        // COD thì căn cứ vào trạng thái đơn hàng
                                        if ($orderStatus === 'Đã giao') {
                                            echo '<span class="badge bg-success">COD - Đã thanh toán</span>';
                                        } else {
                                            echo '<span class="badge bg-warning text-dark">COD - Chưa thanh toán</span>';
                                        }
                                    } elseif ($paymentMethod === 'e_wallet') {
                                        // VNPay thì kiểm tra bảng vnpay_transactions
                                        $vnpayStatus = getVNPayStatus($PDO, $data['info']->id);
                                        if ($vnpayStatus === 'success') {
                                            echo '<span class="badge bg-success">VN Pay -Đã thanh toán</span>';
                                        } elseif ($vnpayStatus === 'pending') {
                                            echo '<span class="badge bg-warning text-dark">VN Pay - Chưa thanh toán</span>';
                                        } else {
                                            // Trạng thái khác hoặc chưa có giao dịch
                                            echo '<span class="badge bg-secondary">Không rõ</span>';
                                        }
                                    } else {
                                        echo '<span class="badge bg-secondary">Không rõ</span>';
                                    }
                                    ?>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($data['info']->created_at)) ?></td>

                                <td>
                                    <?php
                                    $status = $data['info']->status;
                                    $cancelApproved = $data['info']->cancel_approved;
                                    if ($cancelApproved === 1) {
                                        $selectStyle = 'background-color: #dc3545; color: #fff;'; // đỏ
                                    } else {
                                        $statusColors = [
                                            'Chờ xử lý' => 'background-color: #6c757d; color: #fff;',
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
                                        <?php
                                        $statusOptions = [
                                            'Chờ xử lý',
                                            'Đang xử lý',
                                            'Đang vận chuyển',
                                            'Đã giao',
                                        ];

                                        $currentIndex = array_search($status, $statusOptions);
                                        ?>

                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="order_id" value="<?= htmlspecialchars($data['info']->id) ?>">
                                            <select name="status"
                                                class="form-select form-select-sm fw-bold text-center rounded-pill shadow-sm border-0"
                                                style="min-width: 180px; <?= $selectStyle ?>"
                                                onchange="this.form.submit()">
                                                <?php foreach ($statusOptions as $index => $option):
                                                    // Cho phép chỉ trạng thái hiện tại và trạng thái kế tiếp
                                                    $disabled = false;
                                                    if ($index !== $currentIndex && $index !== $currentIndex + 1) {
                                                        $disabled = true;
                                                    }
                                                ?>
                                                    <option value="<?= $option ?>"
                                                        <?= ($status == $option) ? 'selected' : '' ?>
                                                        <?= $disabled ? 'disabled' : '' ?>>
                                                        <?php
                                                        switch ($option) {
                                                            case 'Chờ xử lý':
                                                                echo '⏳ ';
                                                                break;
                                                            case 'Đang xử lý':
                                                                echo '🕒 ';
                                                                break;
                                                            case 'Đang vận chuyển':
                                                                echo '🚚 ';
                                                                break;
                                                            case 'Đã giao':
                                                                echo '✅ ';
                                                                break;
                                                        }
                                                        echo $option;
                                                        ?>
                                                    </option>
                                                <?php endforeach; ?>
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
                                <td class="text-center" style="min-width: 100px;">
                                    <!-- Nút in hóa đơn -->
                                    <a href="invoice.php?id=<?= urlencode($data['info']->id) ?>"
                                        target="_blank"
                                        class="btn btn-success btn-sm">
                                        In hóa đơn
                                    </a>

                                    <?php if ($data['info']->cancel_approved === 1): ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="delete_order_id" value="<?= htmlspecialchars($data['info']->id) ?>">
                                            <button type="submit" class="btn btn-danger btn-sm mt-2">Xóa</button>
                                        </form>
                                    <?php endif; ?>
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
            <script>
                $(document).ready(function() {
                    $('#productsTable').DataTable({
                        "order": [
                            [8, "desc"]
                        ],
                        "language": {
                            "search": "Tìm kiếm:",
                            "lengthMenu": "Hiển thị _MENU_ đơn hàng",
                            "info": "Hiển thị từ _START_ đến _END_ trong tổng _TOTAL_ đơn hàng",
                            "paginate": {
                                "first": "Đầu",
                                "last": "Cuối",
                                "next": "Tiếp",
                                "previous": "Trước"
                            },
                            "emptyTable": "Không có dữ liệu trong bảng",
                            "zeroRecords": "Không tìm thấy sản phẩm phù hợp",
                        }
                    });
                });
            </script>
            <!-- Bootstrap JS (optional) -->
            <!-- <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script> -->
            <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
            <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var successToastEl = document.getElementById('successToast');
                    if (successToastEl) {
                        var toast = new bootstrap.Toast(successToastEl, {
                            delay: 0
                        });
                        toast.show();
                    }

                    var errorToastEl = document.getElementById('errorToast');
                    if (errorToastEl) {
                        var toast = new bootstrap.Toast(errorToastEl, {
                            delay: 0
                        });
                        toast.show();
                    }
                });
            </script>
            </body>

            </html>