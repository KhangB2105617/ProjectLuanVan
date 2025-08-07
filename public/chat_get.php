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

// Lấy tất cả tin nhắn giữa người dùng và staff/admin
$stmt = $PDO->prepare("
    SELECT 
        cm.message,
        cm.created_at,
        sender.username AS sender_name,
        sender.role AS sender_role
    FROM chat_messages cm
    JOIN users sender ON sender.id = cm.user_id
    WHERE (cm.user_id = :uid OR cm.receiver_id = :uid)
    ORDER BY cm.created_at ASC
");
$stmt->execute(['uid' => $userId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Gán tên "Nhân Viên" nếu sender là admin hoặc staff
foreach ($messages as &$msg) {
    if ($msg['sender_role'] === 'admin' || $msg['sender_role'] === 'staff') {
        $msg['sender_name'] = 'Nhân Viên';
    }
}

echo json_encode($messages);