<?php
require_once __DIR__ . '/../../src/bootstrap.php';

$id = $_GET['id'];
$stmt = $PDO->prepare("DELETE FROM discount_codes WHERE id = :id");
$stmt->execute([':id' => $id]);

header("Location: manage_discounts.php");
exit;