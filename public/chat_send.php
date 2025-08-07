<?php
session_start();
require_once __DIR__ . '/../src/bootstrap.php';

if (!isset($_SESSION['id'])) exit;

$userId = $_SESSION['id'];
$data = json_decode(file_get_contents('php://input'), true);
$message = trim($data['message'] ?? '');

if ($message !== '') {
    // Lấy 1 staff bất kỳ để nhận tin nhắn (ví dụ: staff đầu tiên)
    $stmt = $PDO->query("SELECT id FROM users WHERE role IN ('staff', 'admin') ORDER BY id ASC LIMIT 1");
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($staff) {
        $receiverId = $staff['id'];

        $stmt = $PDO->prepare("
            INSERT INTO chat_messages (user_id, receiver_id, message, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $receiverId, $message]);
    }
}