<?php
require_once __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$user_id = $data['user_id'] ?? null;
$sender = $data['sender'] ?? 'admin';
$message = $data['message'] ?? '';

if (!$user_id || !$message) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $PDO->prepare("INSERT INTO chat_messages (user_id, sender, message) VALUES (?, ?, ?)");
$stmt->execute([$user_id, $sender, $message]);

echo json_encode(['success' => true]);