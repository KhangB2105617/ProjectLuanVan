<?php 
include_once __DIR__ . '/../src/partials/header.php';
require_once __DIR__ . '/../src/bootstrap.php';

// Lấy danh sách tin tức
$stmt = $PDO->query("SELECT * FROM product_news ORDER BY created_at DESC");
$newsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="container py-5">
    <h2 class="text-center mb-5 fw-bold text-uppercase">Tin tức sản phẩm</h2>

    <div class="row">
        <?php foreach ($newsList as $news): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-lg border-0 rounded-4 d-flex flex-column">
                    <img 
                        src="/assets/img/<?= htmlspecialchars($news['image']); ?>" 
                        class="card-img-top rounded-top-4" 
                        alt="<?= htmlspecialchars($news['title']); ?>"
                        style="object-fit: cover; height: 200px;"
                    >
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-semibold"><?= htmlspecialchars($news['title']); ?></h5>
                        <p class="card-text text-secondary flex-grow-1"><?= htmlspecialchars($news['summary']); ?></p>
                        <div class="d-flex justify-content-end">
                            <a href="product_news_detail.php?id=<?= $news['id']; ?>" class="btn btn-outline-primary btn-sm px-3 rounded-pill">
                                Xem thêm
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php include_once __DIR__ . '/../src/partials/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>