<?php
require_once __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json');

// Lấy danh sách người dùng (không phải admin) đã từng nhắn tin
$stmt = $PDO->query("
    SELECT DISTINCT u.id, u.username
    FROM users u
    JOIN chat_messages cm ON u.id = cm.user_id
    WHERE u.role != 'admin' AND u.role != 'staff'
    ORDER BY u.username ASC
");

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));