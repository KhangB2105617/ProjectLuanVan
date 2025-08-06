<?php
require_once __DIR__ . '/../src/bootstrap.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id'])) {
    echo json_encode([]);
    exit;
}

$userId = $_SESSION['id'];

// Lấy tin nhắn giữa người dùng và admin
$stmt = $PDO->prepare("SELECT sender, message, created_at FROM chat_messages WHERE user_id = :uid ORDER BY created_at ASC");
$stmt->execute(['uid' => $userId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($messages);