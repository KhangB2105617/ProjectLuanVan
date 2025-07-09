<?php
require_once __DIR__ . '/../../src/bootstrap.php';
use NL\User;

$admin_username = "admin";
$admin_password = "admin123";
$role = "admin";
$email = "admin@123.com";

$user = new User($PDO);

$user->fill([
    'username' => $admin_username,
    'email' => $email,
    'password' => $admin_password,
    'role' => $role,
]);
// Thêm tài khoản admin vào bảng users
if ($user->save()) {
    echo "Tài khoản admin được tạo thành công.";
} else {
    echo "Lỗi: Không thể tạo tài khoản admin.";
}
