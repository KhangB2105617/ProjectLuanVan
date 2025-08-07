<?php
require_once __DIR__ . '/../../src/bootstrap.php';
use NL\Product;

$product = new Product($PDO);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['id'])) {
        header("Location: manage_products.php?error=missing_id");
        exit;
    }

    $id = $_POST['id'];

    if ($product->Softdelete($id)) {
        header("Location: manage_products.php?success=1");
        exit;
    } else {
        header("Location: manage_products.php?error=delete_failed");
        exit;
    }
}
?>