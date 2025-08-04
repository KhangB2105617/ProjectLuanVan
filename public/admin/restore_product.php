<?php
require_once __DIR__ . '/../../src/bootstrap.php';
use NL\Product;

$product = new Product($PDO);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: deleted_products.php");
    exit;
}

$id = (int)$_GET['id'];
$product->restore($id);

header("Location: deleted_products.php?restored=1");
exit;