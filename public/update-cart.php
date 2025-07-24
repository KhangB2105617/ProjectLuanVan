<?php
session_start();
require_once __DIR__ . '/../src/bootstrap.php';

use NL\Product;

$productModel = new Product($PDO);

// Kiểm tra nếu yêu cầu gửi từ AJAX (JSON)
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$isJson = stripos($contentType, 'application/json') !== false;

if ($isJson) {
    // Xử lý AJAX JSON
    $data = json_decode(file_get_contents("php://input"), true);
    $action = $data['action'] ?? null;
    $productId = (int)($data['product_id'] ?? 0);
    $quantity = (int)($data['quantity'] ?? 1);

    if ($productId > 0) {
        switch ($action) {
            case 'update':
                if ($quantity < 1) {
                    echo json_encode(['success' => false, 'message' => 'Số lượng phải lớn hơn 0']);
                    exit;
                }

                $_SESSION['cart'][$productId] = $quantity;

                $product = $productModel->getById($productId);
                $subtotal = $product->price * $quantity;

                // Tính lại tổng giỏ hàng
                $total = 0;
                foreach ($_SESSION['cart'] as $pid => $qty) {
                    $p = $productModel->getById($pid);
                    $total += $p->price * $qty;
                }

                echo json_encode([
                    'success' => true,
                    'subtotal_formatted' => number_format($subtotal, 0, ',', '.'),
                    'total_formatted' => number_format($total, 0, ',', '.')
                ]);
                exit;


            default:
                echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ!']);
                exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Thiếu product_id']);
        exit;
    }
}

// Nếu là form POST truyền thống (submit bằng nút)
$action = $_POST['action'] ?? null;
$productId = (int)($_POST['product_id'] ?? 0);

if ($productId > 0) {
    switch ($action) {
        case 'add':
            $quantity = (int)($_POST['quantity'] ?? 1);
            if (isset($_SESSION['cart'][$productId])) {
                $_SESSION['cart'][$productId] += $quantity;
            } else {
                $_SESSION['cart'][$productId] = $quantity;
            }
            break;

        case 'update':
            $quantity = (int)($_POST['quantity'] ?? 1);
            if ($quantity > 0) {
                $_SESSION['cart'][$productId] = $quantity;
            } else {
                $_SESSION['error'] = "Số lượng không hợp lệ!";
            }
            break;

        case 'remove':
            unset($_SESSION['cart'][$productId]);
            break;

        case 'clear':
            unset($_SESSION['cart']);
            break;

        default:
            break;
    }
} else {
    // Nếu không có product_id
    $_SESSION['error'] = "Không có sản phẩm để xử lý!";
}

header("Location: cart.php");
exit;
