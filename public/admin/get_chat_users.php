<?php
require_once __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json');

// Lấy danh sách người dùng đã từng nhắn tin với admin (admin giả sử có id = 1)
$stmt = $PDO->query("
    SELECT DISTINCT users.id, users.username
    FROM users
    JOIN chat_messages ON users.id = chat_messages.user_id
    WHERE users.id != 2 -- không lấy admin
    ORDER BY users.username ASC
");

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));