<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /unauthorized.php');
    exit;
}

require_once __DIR__ . '/../../src/bootstrap.php';
use NL\Product;

$product = new Product($PDO);

$id = $_GET['id'] ?? null;
$visible = $_GET['visible'] ?? null;

if ($id !== null && ($visible === '0' || $visible === '1')) {
    $newVisible = $visible === '1' ? 0 : 1;
    $stmt = $PDO->prepare("UPDATE products SET is_visible = :newVisible WHERE id = :id");
    $stmt->execute(['newVisible' => $newVisible, 'id' => $id]);
}

header("Location: manage_products.php");
exit;