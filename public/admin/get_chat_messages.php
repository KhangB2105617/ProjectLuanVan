<?php
require_once __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json');

$user_id = $_GET['user_id'] ?? null;
if (!$user_id) {
    echo json_encode([]);
    exit;
}

$stmt = $PDO->prepare("
    SELECT sender, message, created_at
    FROM chat_messages
    WHERE user_id = ?
    ORDER BY created_at ASC
");
$stmt->execute([$user_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));