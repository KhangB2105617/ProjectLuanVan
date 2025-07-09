<?php
include_once __DIR__ . '/../src/partials/header.php';
require_once __DIR__ . '/../src/bootstrap.php';
// Lấy sản phẩm nổi bật
$sql = "SELECT * FROM products WHERE is_featured = 1";
$stmt = $PDO->query($sql);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<main>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 p-0">
                <div id="bannerCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000">
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <div class="d-flex justify-content-center">
                                <img src="assets/img/banner1.png" class="img-fluid banner-img" alt="Khuyến mãi 1">
                                <img src="assets/img/banner2.png" class="img-fluid banner-img" alt="Khuyến mãi 2">
                            </div>
                        </div>
                        <div class="carousel-item">
                            <div class="d-flex justify-content-center">
                                <img src="assets/img/banner3.png" class="img-fluid banner-img" alt="Khuyến mãi 3">
                                <img src="assets/img/banner4.png" class="img-fluid banner-img" alt="Khuyến mãi 4">
                            </div>
                        </div>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#bannerCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#bannerCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="container mt-5">
    <div class="flash-sale-container text-center">
        <img src="assets/img/flashsalebanner.png" class="img-fluid" alt="Flash Sale">
    </div>

    <div class="row justify-content-center mt-4">
        <?php
        // Truy vấn sản phẩm đang giảm giá
        $stmt = $PDO->prepare("SELECT * FROM products WHERE price > 0 ORDER BY price DESC LIMIT 5");
        $stmt->execute();
        $flashSaleProducts = $stmt->fetchAll(PDO::FETCH_OBJ);

        foreach ($flashSaleProducts as $product) :
        ?>
            <div class="col-lg-2 col-md-4 col-sm-6 mb-4 d-flex">
                <a href="/product-details.php?id=<?= $product->id; ?>" class="text-decoration-none text-dark w-100">
                    <div class="card w-100 shadow-sm border rounded-3">
                        <img src="/assets/img/<?= htmlspecialchars($product->image); ?>" class="card-img-top" alt="<?= htmlspecialchars($product->name); ?>">
                        <div class="card-body p-2">
                            <h5 class="card-title"><?= htmlspecialchars($product->name); ?></h5>
                            <div class="d-flex justify-content-between align-items-center">
                                <p class="text-muted text-decoration-line-through mb-0">
                                    <?= number_format($product->original_price, 0, ',', '.'); ?>đ
                                </p>
                                <p class="fw-bold text-danger mb-0" style="font-size: 1.2rem;">
                                    <?= number_format($product->price, 0, ',', '.'); ?>đ
                                </p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

    <div class="container pt-5">
    <h2 class="text-center mb-4">SẢN PHẨM NỔI BẬT</h2>
    <section>
        <div class="row justify-content-center">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-4 d-flex">
                        <a href="/product-details.php?id=<?= htmlspecialchars($product['id']); ?>" class="text-decoration-none text-dark w-100">
                            <div class="card w-100 shadow-sm border rounded-3">
                                <img src="/assets/img/<?= htmlspecialchars($product['image']); ?>" class="card-img-top" alt="<?= htmlspecialchars($product['name']); ?>">
                                <div class="card-body p-2">
                                    <h5 class="card-title">
                                        <?= htmlspecialchars($product['name']); ?>
                                    </h5>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <p class="text-muted text-decoration-line-through mb-0">
                                            <?= number_format($product['original_price'], 0, ',', '.'); ?>đ
                                        </p>
                                        <p class="fw-bold text-danger mb-0" style="font-size: 1.2rem;">
                                            <?= number_format($product['price'], 0, ',', '.'); ?>đ
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-danger">Không có sản phẩm nổi bật nào.</p>
            <?php endif; ?>
        </div>
    </section>
</div>
</main>

<?php
require_once __DIR__ . '/../src/partials/footer.php';
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
