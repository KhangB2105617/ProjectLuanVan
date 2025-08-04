<?php
require_once __DIR__ . '/../../src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;

    if ($id) {
        $stmt = $PDO->prepare("DELETE FROM reviews WHERE id = ?");
        if ($stmt->execute([$id])) {
            header("Location: manage_reviews.php?success=1");
            exit;
        }
    }
    header("Location: manage_reviews.php?error=1");
    exit;
}