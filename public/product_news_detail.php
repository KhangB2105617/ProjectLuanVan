<?php
include_once __DIR__ . '/../src/partials/header.php';
require_once __DIR__ . '/../src/bootstrap.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "<p class='text-center text-danger'>Không tìm thấy bài viết.</p>";
    exit;
}

$stmt = $PDO->prepare("SELECT * FROM product_news WHERE id = ?");
$stmt->execute([$id]);
$news = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$news) {
    echo "<p class='text-center text-danger'>Bài viết không tồn tại.</p>";
    exit;
}
?>

<main class="container py-5">
    <h2 class="mb-4"><?= htmlspecialchars($news['title']); ?></h2>
    <p class="text-muted">Đăng ngày: <?= date('d/m/Y', strtotime($news['created_at'])); ?></p>
    <img src="/assets/img/<?= htmlspecialchars($news['image']); ?>" class="img-fluid mb-4" alt="<?= htmlspecialchars($news['title']); ?>">
    <div>
        <?= nl2br(htmlspecialchars($news['content'])); ?>
    </div>
</main>

<?php include_once __DIR__ . '/../src/partials/footer.php'; ?>