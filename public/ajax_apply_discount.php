<?php
require_once __DIR__ . '/../src/bootstrap.php';

$code = $_POST['code'] ?? '';
$total = floatval($_POST['total'] ?? 0);
$discountAmount = 0;

$stmt = $PDO->prepare("SELECT * FROM discount_codes WHERE code = :code AND (expired_at IS NULL OR expired_at > NOW()) AND used_count < max_usage");
$stmt->execute([':code' => $code]);
$discount = $stmt->fetch(PDO::FETCH_ASSOC);

if ($discount) {
    if ($discount['discount_type'] === 'percent') {
        $discountAmount = $total * ($discount['discount_value'] / 100);
    } else {
        $discountAmount = $discount['discount_value'];
    }
    $discountAmount = min($discountAmount, $total);
}

$newTotal = $total - $discountAmount;

echo json_encode([
    'code' => $code,
    'total' => $total,
    'total_formatted' => number_format($total, 0, ',', '.'),
    'discount_amount' => $discountAmount,
    'discount_formatted' => number_format($discountAmount, 0, ',', '.'),
    'new_total' => $newTotal,
    'new_total_formatted' => number_format($newTotal, 0, ',', '.'),
]);
exit;