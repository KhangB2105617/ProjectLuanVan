<?php
include_once __DIR__ . '/../src/partials/header.php';
require_once __DIR__ . '/../src/bootstrap.php';
// Lấy danh sách mã giảm giá toàn shop
$stmt = $PDO->prepare("
    SELECT * FROM discount_codes
    WHERE 
        (expired_at IS NULL OR expired_at >= NOW())
        AND (max_usage IS NULL OR used_count < max_usage)
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->execute();
$globalDiscounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
$savedDiscounts = [];
if (isset($_SESSION['user_id'])) {
    $stmt = $PDO->prepare("
        SELECT discount_code_id 
        FROM user_discount_codes 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $savedDiscounts = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
// Lấy sản phẩm bán chạy
use NL\Order;

$order = new Order($PDO);
$products = $order->getTopSellingProducts(5); // lấy top 5 bán chạy
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
    <?php if (!empty($globalDiscounts)): ?>
        <div class="container mt-4">
            <h4 class="text-center text-danger mb-3">🎁 Ưu đãi toàn shop - Lưu ngay!</h4>
            <div class="row justify-content-center">
                <?php foreach ($globalDiscounts as $discount): ?>
                    <div class="col-md-4 col-lg-3 mb-3">
                        <div class="card shadow-sm h-100 border border-warning">
                            <div class="card-body text-center">
                                <h5 class="card-title text-primary"><?= htmlspecialchars($discount['code']) ?></h5>
                                <p class="card-text">
                                    <?= $discount['discount_type'] === 'percent'
                                        ? $discount['discount_value'] . '% giảm'
                                        : number_format($discount['discount_value'], 0, ',', '.') . 'đ giảm' ?>
                                    <br>

                                    <?php if (!empty($discount['min_order_amount'])): ?>
                                        <small class="text-muted">
                                            Áp dụng cho đơn từ <?= number_format($discount['min_order_amount'], 0, ',', '.') ?>đ
                                        </small>
                                        <br>
                                    <?php endif; ?>

                                    <small class="text-muted">
                                        <?= $discount['expired_at']
                                            ? 'HSD: ' . date('d/m/Y', strtotime($discount['expired_at']))
                                            : 'Không giới hạn' ?>
                                    </small>
                                </p>
                                <?php
                                $alreadySaved = in_array($discount['id'], $savedDiscounts);
                                if ($alreadySaved):
                                ?>
                                    <button class="btn btn-sm btn-secondary" disabled>Đã lưu</button>
                                <?php else: ?>
                                    <button
                                        class="btn btn-sm btn-success save-discount-btn"
                                        data-code-id="<?= $discount['id'] ?>">
                                        <i class="fas fa-download"></i> Lưu mã
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- FLASH SALE SECTION -->
    <div class="container mt-5">
        <div class="flash-sale-section">
            <div class="flash-sale-container text-center">
                <img src="assets/img/flashsalebanner.png" class="img-fluid" alt="Flash Sale">
            </div>
            <div class="position-relative">
                <button class="nav-left" onclick="scrollFlashSale(-1)">&#10094;</button>
                <div class="flash-sale-scroll mt-4" id="flash-sale-scroll">
                    <?php
                    $stmt = $PDO->prepare("SELECT * FROM products WHERE price > 0 ORDER BY price DESC LIMIT 10");
                    $stmt->execute();
                    $flashSaleProducts = $stmt->fetchAll(PDO::FETCH_OBJ);

                    foreach ($flashSaleProducts as $product):
                        $percent = ($product->original_price > $product->price)
                            ? round((($product->original_price - $product->price) / $product->original_price) * 100)
                            : 0;
                    ?>
                        <div class="flash-sale-item me-3">
                            <a href="/product-details.php?id=<?= $product->id ?>" class="text-decoration-none text-dark w-100">
                                <div class="card w-100 shadow-sm border rounded-3">
                                    <?php if ($percent > 0): ?>
                                        <div class="badge bg-danger text-white position-absolute" style="top: 10px; left: 10px; z-index: 1;">
                                            -<?= $percent ?>%
                                        </div>
                                    <?php endif; ?>
                                    <img src="/assets/img/<?= htmlspecialchars($product->image) ?>" class="card-img-top" alt="<?= htmlspecialchars($product->name) ?>">
                                    <div class="card-body p-2">
                                        <h5 class="card-title product-name"><?= htmlspecialchars($product->name) ?></h5>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <p class="text-muted text-decoration-line-through mb-0">
                                                <?= number_format($product->original_price, 0, ',', '.') ?>đ
                                            </p>
                                            <p class="fw-bold text-danger mb-0" style="font-size: 1.2rem;">
                                                <?= number_format($product->price, 0, ',', '.') ?>đ
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="nav-right" onclick="scrollFlashSale(1)">&#10095;</button>
            </div>
        </div>
    </div>

    <div class="container pt-5">
        <h2 class="text-center mb-4">SẢN PHẨM BÁN CHẠY</h2>
        <section>
            <div class="row justify-content-center">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <div class="col-lg-2 col-md-4 col-sm-6 mb-4 d-flex">
                            <a href="/product-details.php?id=<?= htmlspecialchars($product['id']); ?>" class="text-decoration-none text-dark w-100">
                                <div class="card w-100 shadow-sm border rounded-3 position-relative">
                                    <?php if ($product['original_price'] > $product['price']):
                                        $percent = round((($product['original_price'] - $product['price']) / $product['original_price']) * 100);
                                    ?>
                                        <div class="badge bg-danger text-white position-absolute" style="top: 10px; left: 10px;">
                                            -<?= $percent ?>%
                                        </div>
                                    <?php endif; ?>
                                    <img src="/assets/img/<?= htmlspecialchars($product['image']); ?>" class="card-img-top" alt="<?= htmlspecialchars($product['name']); ?>">
                                    <div class="card-body p-2">
                                        <h5 class="card-title product-name">
                                            <?= htmlspecialchars($product['name']); ?>
                                        </h5>
                                        <p class="mb-0 text-muted"><small>Đã bán: <?= $product['total_quantity'] ?></small></p>
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
<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.save-discount-btn').forEach(button => {
            button.addEventListener('click', function() {
                const codeId = this.dataset.codeId;
                const btn = this;

                fetch('/save_discount.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'code_id=' + encodeURIComponent(codeId)
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            btn.innerText = "Đã lưu";
                            btn.disabled = true;
                            btn.classList.remove("btn-success");
                            btn.classList.add("btn-secondary");
                        } else {
                            alert(data.message);
                        }
                    });
            });
        });
    });
</script>
<script>
    const flashScroll = document.getElementById("flash-sale-scroll");
    const step = 511; // Mỗi lần cuộn bao nhiêu px (sát với độ rộng flash-sale-item)
    const interval = 3000; // 3 giây
    let scrollPos = 0;

    setInterval(() => {
        const maxScroll = flashScroll.scrollWidth - flashScroll.clientWidth;

        if (scrollPos + step >= maxScroll) {
            scrollPos = 0;
        } else {
            scrollPos += step;
        }

        flashScroll.scrollTo({
            left: scrollPos,
            behavior: "smooth"
        });
    }, interval);

    function scrollFlashSale(direction) {
        const flashScroll = document.getElementById("flash-sale-scroll");
        const step = 200; // px - tương đương với độ rộng sản phẩm
        flashScroll.scrollBy({
            left: direction * step,
            behavior: "smooth"
        });
    }
</script>

<?php
require_once __DIR__ . '/../src/partials/footer.php';
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>