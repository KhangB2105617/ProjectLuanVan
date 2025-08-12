<?php
include_once __DIR__ . '/../src/partials/header.php'; // Bao gồm phần header
require_once __DIR__ . '/../src/bootstrap.php'; // Kết nối cơ sở dữ liệu

use NL\Product;

// Lấy ID sản phẩm từ URL
$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$productModel = new Product($PDO);
$product = $productModel->getById($productId); // Lấy thông tin sản phẩm theo ID
$productImages = $productModel->getImages($productId);

// Nếu sản phẩm không tồn tại, hiển thị lỗi
if (!$product) {
    echo "<div class='container text-center mt-5'>
            <h1 class='text-danger'>Sản phẩm không tồn tại!</h1>
          </div>";
    include_once __DIR__ . '/../src/partials/footer.php';
    exit;
}

// Kiểm tra đăng nhập
$isLoggedIn = isset($_SESSION['id']);

// Lấy danh sách đánh giá từ database
$stmt = $PDO->prepare("SELECT * FROM reviews WHERE product_id = ? ORDER BY created_at DESC");
$stmt->execute([$productId]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tính điểm trung bình và số lượng đánh giá
$totalReviews = count($reviews);
$averageRating = $totalReviews ? array_sum(array_column($reviews, 'rating')) / $totalReviews : 0;

// Đếm số lượng đánh giá theo từng mức sao
$ratingCounts = array_fill(1, 5, 0);
foreach ($reviews as $review) {
    $ratingCounts[$review['rating']]++;
}
// Lấy chi tiết kỹ thuật từ bảng product_details
$stmt = $PDO->prepare("SELECT * FROM product_details WHERE product_id = ?");
$stmt->execute([$productId]);
$details = $stmt->fetch(PDO::FETCH_ASSOC);

$categoryId = $product->category_id;

if (in_array($categoryId, [1, 2])) {
    $title = "Đồng hồ " . $product->name;
} else {
    $title = $product->name;
}
?>

<div class="wrapper">
    <main class="content">
        <div class="container mt-4 mb-5">
            <div class="row">
                <div class="col-md-4 d-flex flex-column align-items-center">
                    <div class="position-relative w-100 mb-3">
                        <img id="mainImage" src="/assets/img/<?= htmlspecialchars($product->image); ?>"
                            class="img-fluid rounded shadow"
                            alt="<?= htmlspecialchars($product->name); ?>"
                            style="max-width: 100%; height: auto;">

                        <!-- Nút mũi tên -->
                        <button onclick="prevImage()" class="btn btn-light position-absolute top-50 start-0 translate-middle-y">
                            &#10094;
                        </button>
                        <button onclick="nextImage()" class="btn btn-light position-absolute top-50 end-0 translate-middle-y">
                            &#10095;
                        </button>
                    </div>

                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <!-- Thumbnail ảnh chính -->
                        <img src="/assets/img/<?= htmlspecialchars($product->image); ?>"
                            class="img-thumbnail" style="height: 60px; width: auto; cursor: pointer;"
                            onclick="showImage(0)">
                        <?php foreach ($productImages as $index => $img): ?>
                            <img src="/assets/img/<?= htmlspecialchars($img); ?>"
                                class="img-thumbnail" style="height: 60px; width: auto; cursor: pointer;"
                                onclick="showImage(<?= $index + 1 ?>)">
                        <?php endforeach; ?>
                    </div>
                </div>

                <script>
                    const imagePaths = [
                        "<?= htmlspecialchars($product->image); ?>",
                        <?php foreach ($productImages as $img): ?> "<?= htmlspecialchars($img); ?>",
                        <?php endforeach; ?>
                    ];
                    let currentIndex = 0;

                    function showImage(index) {
                        if (index >= 0 && index < imagePaths.length) {
                            currentIndex = index;
                            document.getElementById('mainImage').src = "/assets/img/" + imagePaths[index];
                        }
                    }

                    function prevImage() {
                        currentIndex = (currentIndex - 1 + imagePaths.length) % imagePaths.length;
                        showImage(currentIndex);
                    }

                    function nextImage() {
                        currentIndex = (currentIndex + 1) % imagePaths.length;
                        showImage(currentIndex);
                    }
                </script>

                <div class="col-md-8">
                    <h1 class="mb-2"><?= htmlspecialchars($title); ?></h1>
                    <p class="text-muted"><?= htmlspecialchars($product->category_name); ?></p>
                    <p class="text-muted text-decoration-line-through mb-0">
                        <?= number_format($product->original_price, 0, ',', '.'); ?>đ
                    </p>
                    <p class="price text-danger fs-4 fw-bold">
                        <?= number_format($product->price, 0, ',', '.'); ?>đ
                    </p>
                    <p class="text-success">Kho còn: <strong><?= (int)$product->quantity; ?></strong> sản phẩm</p>
                    <p><?= nl2br(htmlspecialchars($product->description)); ?></p>

                    <div class="mt-4 d-flex gap-2">
                        <?php if ((int)$product->quantity > 0): ?>
                            <form method="post" action="update-cart.php" onsubmit="return checkAllConditions(event);">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?= $product->id; ?>">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <label for="quantity" class="form-label mb-0">Số lượng:</label>
                                        <input type="number" id="quantity" name="quantity" value="1" class="form-control" style="width: 80px;" min="1" max="<?= (int)$product->quantity ?>" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Thêm vào giỏ hàng</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>Hết hàng</button>
                        <?php endif; ?>

                        <script>
                            function checkLogin(event) {
                                var isLoggedIn = <?= json_encode($isLoggedIn); ?>;
                                if (!isLoggedIn) {
                                    event.preventDefault();
                                    alert("Bạn cần đăng nhập để thêm sản phẩm vào giỏ hàng!");
                                    window.location.href = "login.php";
                                    return false;
                                }
                                return true;
                            }

                            function checkQuantity() {
                                const quantity = <?= (int)$product->quantity ?>;
                                if (quantity <= 0) {
                                    alert("Sản phẩm đã hết hàng. Vui lòng chọn sản phẩm khác.");
                                    return false;
                                }
                                return true;
                            }

                            function checkAllConditions(event) {
                                if (!checkLogin(event)) return false;
                                if (!checkQuantity()) {
                                    event.preventDefault();
                                    return false;
                                }
                                return true;
                            }
                        </script>

                        <?php
                        $canReview = false;
                        if ($isLoggedIn && isset($_SESSION['username'])) {
                            $currentUsername = $_SESSION['username'];
                            $stmt = $PDO->prepare("SELECT COUNT(*) FROM orders o JOIN order_items oi ON o.id = oi.order_id WHERE o.username = ? AND oi.product_id = ? AND o.status = 'Đã giao'");
                            $stmt->execute([$currentUsername, $productId]);
                            $canReview = $stmt->fetchColumn() > 0;
                        }
                        ?>

                        <a href="product.php" class="btn btn-secondary">⬅️ Quay lại</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="container mt-4">

            <!-- Tiêu đề -->
            <h5 class="fw-bold">Thông số sản phẩm - <?= htmlspecialchars($product->name); ?></h5>

            <?php if ($details): ?>
    <div class="card mt-4" style="max-width: 700px;">
        <div class="card-header d-flex">
            <button class="btn btn-primary me-2" disabled>Thông số kỹ thuật</button>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <?php if ($product->category_id == 3): ?>
                    <!-- Chỉ hiển thị các cột dành cho phụ kiện -->
                    <tr>
                        <th>Đối tượng sử dụng:</th>
                        <td><?= htmlspecialchars($details['target_user']) ?></td>
                    </tr>
                    <tr>
                        <th>Chất liệu dây:</th>
                        <td><?= htmlspecialchars($details['strap_material']) ?></td>
                    </tr>
                    <tr>
                        <th>Độ rộng dây:</th>
                        <td><?= htmlspecialchars($details['strap_width']) ?></td>
                    </tr>
                    <tr>
                        <th>Sản xuất tại:</th>
                        <td><?= htmlspecialchars($details['manufacture_location']) ?></td>
                    </tr>
                    <tr>
                        <th>Thương hiệu của:</th>
                        <td><?= htmlspecialchars($product->brand_origin) ?></td>
                    </tr>
                    <tr>
                        <th>Hãng:</th>
                        <td><?= htmlspecialchars($product->brand_name) ?></td>
                    </tr>
                <?php else: ?>
                    <!-- Các cột đầy đủ cho đồng hồ -->
                    <tr>
                        <th>Đối tượng sử dụng:</th>
                        <td><?= htmlspecialchars($details['target_user']) ?></td>
                    </tr>
                    <tr>
                        <th>Đường kính mặt:</th>
                        <td><?= htmlspecialchars($details['diameter']) ?></td>
                    </tr>
                    <tr>
                        <th>Chất liệu dây:</th>
                        <td><?= htmlspecialchars($details['strap_material']) ?></td>
                    </tr>
                    <tr>
                        <th>Độ rộng dây:</th>
                        <td><?= htmlspecialchars($details['strap_width']) ?></td>
                    </tr>
                    <tr>
                        <th>Chất liệu khung viền:</th>
                        <td><?= htmlspecialchars($details['frame_material']) ?></td>
                    </tr>
                    <tr>
                        <th>Độ dày mặt:</th>
                        <td><?= htmlspecialchars($details['thickness']) ?></td>
                    </tr>
                    <tr>
                        <th>Chất liệu mặt kính:</th>
                        <td><?= htmlspecialchars($details['glass_material']) ?></td>
                    </tr>
                    <tr>
                        <th>Thời gian sử dụng pin:</th>
                        <td><?= htmlspecialchars($details['battery_life']) ?></td>
                    </tr>
                    <tr>
                        <th>Kháng nước:</th>
                        <td><?= htmlspecialchars($details['water_resistance']) ?></td>
                    </tr>
                    <tr>
                        <th>Tiện ích:</th>
                        <td><?= nl2br(htmlspecialchars($details['utilities'])) ?></td>
                    </tr>
                    <tr>
                        <th>Nguồn năng lượng:</th>
                        <td><?= htmlspecialchars($details['power_source']) ?></td>
                    </tr>
                    <tr>
                        <th>Loại máy:</th>
                        <td><?= htmlspecialchars($details['movement_type']) ?></td>
                    </tr>
                    <tr>
                        <th>Sản xuất tại:</th>
                        <td><?= htmlspecialchars($details['manufacture_location']) ?></td>
                    </tr>
                    <tr>
                        <th>Thương hiệu của:</th>
                        <td><?= htmlspecialchars($product->brand_origin) ?></td>
                    </tr>
                    <tr>
                        <th>Hãng:</th>
                        <td><?= htmlspecialchars($product->brand_name) ?></td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
<?php endif; ?>

        </div>
        <style>
            .rating-summary h2 {
                font-size: 2rem;
                font-weight: bold;
            }

            .rating-summary span {
                font-size: 1.5rem;
            }

            .progress {
                height: 10px;
                border-radius: 5px;
                background-color: #eee;
            }

            .progress-bar {
                border-radius: 5px;
            }

            .review-container .review {
                background: #fff;
                border-radius: 10px;
                padding: 15px;
                box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            }

            .btn-primary {
                background-color: #007bff;
                border-color: #007bff;
            }

            .btn-primary:hover {
                background-color: #0056b3;
            }

            /* Modal CSS */
            .review-modal {
                display: none;
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: white;
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
                z-index: 1000;
            }

            .overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
            }
        </style>

        <div class="container mt-4 mb-5">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Đánh giá từ khách hàng</h3>
            </div>
            <hr>

            <div class="d-flex justify-content-between align-items-center mb-3" style="max-width: 50%;">
                <div class="rating-summary d-flex align-items-center">
                    <h2 class="me-3 text-warning" style="font-size: 1.5rem;"> <?= number_format($averageRating, 1); ?> </h2>
                    <div style="font-size: 1rem;" class="text-warning">
                        <?php
                        $fullStars = floor($averageRating);
                        $halfStar = ($averageRating - $fullStars) >= 0.5;
                        $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                        ?>
                        <?php for ($i = 0; $i < $fullStars; $i++): ?>
                            <i class="fas fa-star"></i>
                        <?php endfor; ?>
                        <?php if ($halfStar): ?>
                            <i class="fas fa-star-half-alt"></i>
                        <?php endif; ?>
                        <?php for ($i = 0; $i < $emptyStars; $i++): ?>
                            <i class="far fa-star"></i>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php if ($canReview): ?>
                    <button id="toggleReviewForm" class="btn btn-primary">✍️ Viết đánh giá</button>
                <?php elseif ($isLoggedIn): ?>
                    <p class="text-muted fst-italic">Bạn cần mua sản phẩm để có thể đánh giá.</p>
                <?php else: ?>
                    <p class="text-muted fst-italic">* Vui lòng đăng nhập để viết đánh giá.</p>
                <?php endif; ?>

            </div>

            <?php for ($i = 5; $i >= 1; $i--): ?>
                <div class="d-flex align-items-center" style="max-width: 50%;">
                    <span><?= $i; ?> ⭐</span>
                    <div class="progress w-50 mx-2">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?= ($totalReviews ? ($ratingCounts[$i] / $totalReviews) * 100 : 0); ?>%"></div>
                    </div>
                    <span><?= $ratingCounts[$i]; ?> đánh giá</span>
                </div>
            <?php endfor; ?>

            <div class="review-container mt-4">
                <?php if ($reviews): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review border p-3 mb-3 rounded shadow-sm">
                            <strong><?= htmlspecialchars($review['customer_name']); ?></strong> -
                            <span class="text-warning">
                                <?php
                                $full = (int)$review['rating'];
                                for ($i = 0; $i < $full; $i++) echo '<i class="fas fa-star"></i>';
                                for ($i = $full; $i < 5; $i++) echo '<i class="far fa-star"></i>';
                                ?>
                            </span>

                            <p><?= nl2br(htmlspecialchars($review['comment'])); ?></p>
                            <small class="text-muted"><?= $review['created_at']; ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Chưa có đánh giá nào.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modal Form -->
        <div class="overlay" id="overlay"></div>
        <div class="review-modal" id="reviewForm">
            <h4>Viết đánh giá của bạn</h4>
            <form action="submit_review.php" method="POST">
                <input type="hidden" name="product_id" value="<?= $product->id; ?>">
                <input type="hidden" name="product_name" value="<?= htmlspecialchars($product->name); ?>">
                <div class="mb-3">
                    <label for="customer_name">Tên của bạn:</label>
                    <input type="text" name="customer_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="rating">Đánh giá:</label>
                    <select name="rating" class="form-select" required>
                        <option value="5">⭐⭐⭐⭐⭐</option>
                        <option value="4">⭐⭐⭐⭐</option>
                        <option value="3">⭐⭐⭐</option>
                        <option value="2">⭐⭐</option>
                        <option value="1">⭐</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="comment">Nhận xét:</label>
                    <textarea name="comment" class="form-control" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-success">Gửi đánh giá</button>
            </form>
        </div>

        <script>
            const toggleBtn = document.getElementById("toggleReviewForm");
            const reviewForm = document.getElementById("reviewForm");
            const overlay = document.getElementById("overlay");

            if (toggleBtn && reviewForm && overlay) {
                toggleBtn.addEventListener("click", function() {
                    reviewForm.style.display = "block";
                    overlay.style.display = "block";
                });

                overlay.addEventListener("click", function() {
                    reviewForm.style.display = "none";
                    overlay.style.display = "none";
                });
            }
        </script>
    </main>
</div>

<?php include_once __DIR__ . '/../src/partials/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>