<?php  
require_once __DIR__ . '/../../src/bootstrap.php';
header('Content-Type: application/json');

$user_id = $_GET['user_id'] ?? null;
if (!$user_id) {
    echo json_encode([]);
    exit;
}

session_start();
if (!isset($_SESSION['id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    echo json_encode([]);
    exit;
}

$stmt = $PDO->prepare("
    SELECT 
        cm.message,
        cm.created_at,
        sender.username AS sender_name,
        sender.role AS sender_role,
        receiver.username AS receiver_name,
        receiver.role AS receiver_role
    FROM chat_messages cm
    JOIN users sender ON cm.user_id = sender.id
    JOIN users receiver ON cm.receiver_id = receiver.id
    WHERE 
        (cm.user_id = :user_id AND receiver.role IN ('admin', 'staff'))
        OR 
        (cm.receiver_id = :user_id AND sender.role IN ('admin', 'staff'))
    ORDER BY cm.created_at ASC
");
$stmt->execute(['user_id' => $user_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));