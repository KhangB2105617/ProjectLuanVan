<?php
session_start();
require_once __DIR__ . '/../src/bootstrap.php';

use NL\User;
use NL\CartItem;

// Xử lý khi biểu mẫu được gửi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailOrUsername = trim($_POST['email_or_username']);
    $password = $_POST['password'];

    if (empty($emailOrUsername) || empty($password)) {
        $_SESSION['error'] = "Vui lòng điền đầy đủ thông tin.";
    } else {
        // Tạo đối tượng User
        $user = new User($PDO);

        // Tìm kiếm người dùng
        $userRecord = $user->findByEmailOrUsername($emailOrUsername);

        // Kiểm tra nếu người dùng tồn tại và mật khẩu chính xác
        if ($userRecord && password_verify($password, $userRecord->password)) {
            // Đăng nhập thành công, lưu thông tin người dùng vào session
            $_SESSION['id'] = $userRecord->id;
            $_SESSION['user_id'] = $userRecord->id;
            $_SESSION['username'] = $userRecord->username;
            $_SESSION['role'] = $userRecord->role;
            $_SESSION['customer_email'] = $userRecord->email; // Lưu email cho việc tra cứu đơn hàng sau này
            $_SESSION['avatar'] = $userRecord->avatar ?? null;

            $cartItemModel = new CartItem($PDO);

            // Sau khi xác thực thành công
            $userId = $userRecord->id;
            $_SESSION['user_id'] = $userId;

            // Chuyển session cart -> cart_items
            if (!empty($_SESSION['cart'])) {
                require_once __DIR__ . '/../src/bootstrap.php';
                $cartItemModel = new \NL\CartItem($PDO);

                foreach ($_SESSION['cart'] as $productId => $quantity) {
                    $cartItemModel->add($userId, $productId, $quantity);
                }

                // Xóa giỏ hàng trong session sau khi chuyển
                unset($_SESSION['cart']);
            }


            // Luôn khôi phục giỏ hàng từ DB để frontend có thể dùng session
            $cartItems = $cartItemModel->getByUser($userRecord->id);
            $_SESSION['cart'] = [];
            foreach ($cartItems as $item) {
                $_SESSION['cart'][$item->product_id] = $item->quantity;
            }

            // Chuyển hướng theo vai trò
            if ($userRecord->role === 'admin') {
                header("Location: admin/settingadmin.php");
            } elseif ($userRecord->role === 'staff') {
                header("Location: admin/staff_dashboard.php");
            } elseif ($userRecord->role === 'customer') {
                header("Location: index.php"); // Trang chủ cho khách hàng
            }

            exit();
        } else {
            // Sai thông tin đăng nhập
            $_SESSION['error'] = "Tên đăng nhập hoặc mật khẩu không đúng.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(to right, #141e30, #243b55);
            color: white;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Poppins', sans-serif;
        }

        .login-container {
            max-width: 400px;
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            text-align: center;
            backdrop-filter: blur(10px);
        }

        .login-container h2 {
            font-weight: 600;
            color: #fff;
            margin-bottom: 20px;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }

        .error-message {
            color: #ff4d4d;
            margin-bottom: 15px;
        }

        .btn-custom {
            background: #ff4d4d;
            border: none;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .btn-custom:hover {
            background: #e63946;
        }
    </style>
    </style>
</head>

<body>
    <div class="login-container text-center">
        <h2>Đăng Nhập</h2>
        <?php if (isset($_SESSION['error'])): ?>
            <p class="error-message"><?= htmlspecialchars($_SESSION['error']); ?></p>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form action="" method="post">
            <div class="form-group">
                <input
                    type="text"
                    id="email_or_username"
                    name="email_or_username"
                    class="form-control"
                    placeholder="Tên đăng nhập hoặc Email"
                    value="<?= isset($emailOrUsername) ? htmlspecialchars($emailOrUsername) : '' ?>"
                    required>
            </div>
            <div class="form-group">
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="Mật khẩu"
                    required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Đăng Nhập</button>
            <p class="mt-3 text-center">
                Chưa có tài khoản? <a href="register.php" class="text-white font-weight-bold">Đăng ký ngay</a>
            </p>
        </form>
    </div>
</body>

</html>