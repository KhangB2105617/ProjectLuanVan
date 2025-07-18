<?php
include_once __DIR__ . '/../src/partials/header.php';
require_once __DIR__ . '/../src/bootstrap.php';

use NL\Product;

$product = new Product($PDO);

// Kiểm tra nếu có tham số brand trong URL
$brandFilter = isset($_GET['brand']) ? $_GET['brand'] : null;
$sortOrder = isset($_GET['sort']) ? $_GET['sort'] : null;

$brands = $product->getBrands(); // Lấy danh sách thương hiệu
$products = $brandFilter ? $product->getProductsByBrand($brandFilter, $sortOrder) : $product->getAllSorted($sortOrder); // Lọc theo brand nếu có
?>

<main>
    <div class="container pt-3"> <!-- Đổi từ container-fluid sang container để căn giữa -->
        <!-- Danh sách thương hiệu -->
        <section class="mb-4">
    <h4 class="fw-bold text-center">THƯƠNG HIỆU</h4>
    <div class="d-flex flex-wrap justify-content-center gap-2">
        <?php foreach ($brands as $brand): ?>
            <a href="?brand=<?= urlencode($brand['brand']); ?>" class="btn btn-outline-primary p-2">
                <img src="/assets/img/<?= htmlspecialchars($brand['brand_image']); ?>" 
                     alt="<?= htmlspecialchars($brand['brand']); ?>" 
                     style="height: 40px; width: auto; max-width: 100px; object-fit: contain;">
            </a>
        <?php endforeach; ?>
    </div>
</section>

        <!-- bộ lọc sắp xếp -->
        <div class="row mb-3">
            <div class="col-12">
                <section class="d-flex align-items-center">
                    <h5 class="fw-bold me-3">Sắp xếp theo</h5>
                    <a href="?sort=desc" class="btn btn-light <?= $sortOrder == 'desc' ? 'active' : '' ?>">
                        <i class="fas fa-sort-amount-down"></i> Giá Cao - Thấp
                    </a>
                    <a href="?sort=asc" class="btn btn-light <?= $sortOrder == 'asc' ? 'active' : '' ?>">
                        <i class="fas fa-sort-amount-up"></i> Giá Thấp - Cao
                    </a>
                </section>
            </div>
        </div>
        <!-- Danh sách sản phẩm -->
        <section>
            <div class="row justify-content-center"> <!-- Căn giữa các sản phẩm -->
                <?php foreach ($products as $product): ?>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-4 d-flex"> <!-- 5 sản phẩm trên 1 hàng -->
                        <a href="<?= '/product-details.php?id=' . $product->id; ?>" class="text-decoration-none text-dark w-100">
                            <div class="card w-100 shadow-sm border rounded-3"> <!-- Thêm bóng và viền bo -->
                                <img src="/assets/img/<?= htmlspecialchars($product->image); ?>" class="card-img-top" alt="<?= htmlspecialchars($product->name); ?>">
                                <div class="card-body p-2"> <!-- Căn giữa nội dung -->
                                    <h5 class="card-title" style="white-space: normal; overflow: visible;">
                                        <?= htmlspecialchars($product->name); ?>
                                    </h5>

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
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../src/partials/footer.php'; ?>