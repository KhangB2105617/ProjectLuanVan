<?php
session_start();
require_once __DIR__ . '/../src/bootstrap.php';

use NL\User;
use NL\Paginator;

$User = new User($PDO);
$limit = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? (int)$_GET['limit'] : 5;
$page = (isset($_GET['page']) && is_numeric($_GET['page'])) ? (int)$_GET['page'] : 1;
$paginator = new Paginator(
    totalRecords: $User->count(),
    recordsPerPage: $limit,
    currentPage: $page
);
$Users = $User->paginate($paginator->recordOffset, $paginator->recordsPerPage);
$pages = $paginator->getPages(length: 3);

// Xử lý khi biểu mẫu được gửi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $role = "customer";

    // Tạo một đối tượng User
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Mật khẩu xác nhận không khớp.";
    } else {
        $user = new User($PDO);
        $user->fill([
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'role' => $role,
        ]);

        // Kiểm tra lỗi
        $errors = $user->validate($_POST);

        // Kiểm tra xem email đã tồn tại chưa
        if ($user->emailExists($email)) {
            $errors['email'] = "Email này đã được đăng ký. Vui lòng sử dụng email khác.";
        }

        // Nếu có lỗi, hiển thị thông báo
        if (!empty($errors)) {
            $_SESSION['error'] = implode("<br>", $errors);
        } else {
            // Thêm người dùng mới vào cơ sở dữ liệu
            $user->fill([
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'role' => $role,
            ]);

            if ($user->save()) {
                $_SESSION['success'] = "Đăng ký thành công! Bạn có thể đăng nhập.";
                header("Location: login.php");
                exit();
            } else {
                $_SESSION['error'] = "Có lỗi xảy ra. Vui lòng thử lại.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký</title>
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

        .register-container {
            max-width: 400px;
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            text-align: center;
            backdrop-filter: blur(10px);
        }

        .register-container h2 {
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

        .success-message {
            color: #4CAF50;
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
</head>

<body>
    <div class="login-container text-center">
        <h2>Đăng Ký Người Dùng</h2>

        <?php
        if (isset($_SESSION['error'])) {
            echo "<div class='error-message'>" . $_SESSION['error'] . "</div>";
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo "<div class='success-message'>" . $_SESSION['success'] . "</div>";
            unset($_SESSION['success']);
        }
        ?>

        <form action="" method="post">
            <div class="form-group">
                <input type="text" id="username" name="username" class="form-control" placeholder="Username" value="<?= isset($username) ? htmlspecialchars($username) : '' ?>" required>
            </div>
            <div class="form-group">
                <input type="email" id="email" name="email" class="form-control" placeholder="Email" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" required>
            </div>
            <div class="form-group">
                <input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
            </div>
            <div class="form-group">
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Đăng Ký</button>
            <p class="mt-3 text-center">
            Đã có tài khoản? <a href="login.php" class="text-white font-weight-bold">Đăng nhập ngay</a>
        </p>
        </form>
    </div>
</body>

</html>