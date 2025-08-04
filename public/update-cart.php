<?php
session_start();
require_once __DIR__ . '/../src/bootstrap.php';

use NL\Product;
use NL\CartItem;

$productModel = new Product($PDO);
$cartItemModel = new CartItem($PDO);

// --- XỬ LÝ AJAX JSON ---
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$isJson = stripos($contentType, 'application/json') !== false;

if ($isJson) {
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

                $product = $productModel->getById($productId);
                if (!$product) {
                    echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
                    exit;
                }

                if ($quantity > $product->quantity) {
                    echo json_encode([
                        'success' => false,
                        'message' => "Không đủ hàng tồn kho! Hiện còn: {$product->quantity}"
                    ]);
                    exit;
                }

                if (isset($_SESSION['user_id'])) {
                    $userId = (int)$_SESSION['user_id'];

                    // Cập nhật giỏ hàng DB
                    $stmt = $PDO->prepare("
                        UPDATE cart_items SET quantity = :quantity, updated_at = NOW()
                        WHERE user_id = :user_id AND product_id = :product_id
                    ");
                    $stmt->execute([
                        ':user_id' => $userId,
                        ':product_id' => $productId,
                        ':quantity' => $quantity
                    ]);
                } else {
                    $_SESSION['cart'][$productId] = $quantity;
                }

                $subtotal = $product->price * $quantity;

                // Tính tổng
                $total = 0;
                if (isset($_SESSION['user_id'])) {
                    $userId = (int)$_SESSION['user_id'];
                    $items = $cartItemModel->getByUser($userId);
                    foreach ($items as $item) {
                        $total += $item->price * $item->quantity;
                    }
                } else {
                    foreach ($_SESSION['cart'] ?? [] as $pid => $qty) {
                        $p = $productModel->getById($pid);
                        if ($p) {
                            $total += $p->price * $qty;
                        }
                    }
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

// --- XỬ LÝ FORM POST ---
$action = $_POST['action'] ?? null;
$productId = (int)($_POST['product_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

if ($productId > 0) {
    switch ($action) {
        case 'add':
            $product = $productModel->getById($productId);

            if (!$product) {
                $_SESSION['error'] = "Sản phẩm không tồn tại!";
                break;
            }

            if ($product->quantity < $quantity) {
                $_SESSION['error'] = "Không đủ tồn kho! Hiện còn: {$product->quantity}";
                break;
            }

            if (isset($_SESSION['user_id'])) {
                $userId = (int)$_SESSION['user_id'];
                $currentQty = $cartItemModel->getQuantity($userId, $productId);
                $newQty = $currentQty + $quantity;

                if ($newQty > $product->quantity) {
                    $_SESSION['error'] = "Chỉ còn {$product->quantity} sản phẩm trong kho.";
                    break;
                }

                $cartItemModel->add($userId, $productId, $quantity);
            } else {
                $currentInCart = $_SESSION['cart'][$productId] ?? 0;
                $newQuantity = $currentInCart + $quantity;
                if ($newQuantity > $product->quantity) {
                    $_SESSION['error'] = "Chỉ còn {$product->quantity} sản phẩm trong kho.";
                    break;
                }
                $_SESSION['cart'][$productId] = $newQuantity;
            }

            $_SESSION['success'] = "Đã thêm $quantity sản phẩm vào giỏ.";
            break;

        case 'update':
            $product = $productModel->getById($productId);

            if (!$product) {
                $_SESSION['error'] = "Sản phẩm không tồn tại!";
                break;
            }

            if ($quantity < 1) {
                $_SESSION['error'] = "Số lượng phải lớn hơn 0!";
                break;
            }

            if ($quantity > $product->quantity) {
                $_SESSION['error'] = "Không đủ tồn kho! Hiện còn: {$product->quantity}";
                break;
            }

            if (isset($_SESSION['user_id'])) {
                $userId = (int)$_SESSION['user_id'];
                $stmt = $PDO->prepare("UPDATE cart_items SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$quantity, $userId, $productId]);
            } else {
                $_SESSION['cart'][$productId] = $quantity;
            }

            $_SESSION['success'] = "Đã cập nhật số lượng.";
            break;

        case 'remove':
            if (isset($_SESSION['user_id'])) {
                $userId = (int)$_SESSION['user_id'];
                $stmt = $PDO->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$userId, $productId]);
            } else {
                unset($_SESSION['cart'][$productId]);
            }
            $_SESSION['success'] = "Đã xóa sản phẩm khỏi giỏ.";
            break;

        case 'clear':
            if (isset($_SESSION['user_id'])) {
                $userId = (int)$_SESSION['user_id'];
                $cartItemModel->clear($userId);
            } else {
                unset($_SESSION['cart']);
            }
            $_SESSION['success'] = "Đã xóa toàn bộ giỏ hàng.";
            break;

        default:
            $_SESSION['error'] = "Hành động không hợp lệ!";
            break;
    }
} else {
    $_SESSION['error'] = "Không có sản phẩm để xử lý!";
}

header("Location: cart.php");
exit;
