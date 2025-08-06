<?php
session_start();
require_once __DIR__ . '/../src/bootstrap.php';

if (!isset($_SESSION['id'])) exit;

$userId = $_SESSION['id'];
$data = json_decode(file_get_contents('php://input'), true);
$message = trim($data['message'] ?? '');

if ($message !== '') {
    $stmt = $PDO->prepare("INSERT INTO chat_messages (user_id, sender, message) VALUES (?, 'user', ?)");
    $stmt->execute([$userId, $message]);
}
