<?php
require_once __DIR__ . '/../../src/bootstrap.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

session_start();
if (!isset($_SESSION['id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$sender_id = $_SESSION['id'];
$receiver_id = $data['receiver_id'] ?? null;
$message = trim($data['message'] ?? '');

if (!$receiver_id || $message === '') {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $PDO->prepare("INSERT INTO chat_messages (user_id, receiver_id, message) VALUES (?, ?, ?)");
$stmt->execute([$sender_id, $receiver_id, $message]);

echo json_encode(['success' => true]);