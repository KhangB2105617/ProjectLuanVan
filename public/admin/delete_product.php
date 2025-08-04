<?php
require_once __DIR__ . '/../../src/bootstrap.php';
use NL\Product;

$product = new Product($PDO);
$id = $_POST['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($product->Softdelete($id)) {
        header("Location: manage_products.php?success=1");
        exit();
    } else {
        $error = "Xóa sản phẩm thất bại.";
    }
}
?>