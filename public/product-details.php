<?php
include_once __DIR__ . '/../src/partials/header.php'; // Bao gồm phần header
require_once __DIR__ . '/../src/bootstrap.php'; // Kết nối cơ sở dữ liệu

use NL\Product;

// Lấy ID sản phẩm từ URL
$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$productModel = new Product($PDO);
$product = $productModel->getById($productId); // Lấy thông tin sản phẩm theo ID

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
?>

<div class="wrapper">
    <main class="content">
        <div class="container mt-4 mb-5">
            <div class="row">
                <!-- Hình ảnh sản phẩm -->
                <div class="col-md-4 d-flex justify-content-center">
                    <img src="/assets/img/<?= htmlspecialchars($product->image); ?>" class="img-fluid rounded shadow"
                        alt="<?= htmlspecialchars($product->name); ?>" style="max-width: 100%; height: auto;">
                </div>

                <!-- Thông tin sản phẩm -->
                <div class="col-md-8">
                    <h1 class="mb-2"><?= htmlspecialchars($product->name); ?></h1>
                    <p class="text-muted"><?= htmlspecialchars($product->category); ?></p>
                    <p class="text-muted text-decoration-line-through mb-0">
                        <?= number_format($product->original_price, 0, ',', '.'); ?>đ
                    </p>
                    <p class="price text-danger fs-4 fw-bold">
                        <?= number_format($product->price, 0, ',', '.'); ?>đ
                    </p>
                    <p><?= nl2br(htmlspecialchars($product->description)); ?></p>

                    <div class="mt-4 d-flex gap-2">
                        <form method="post" action="update-cart.php" onsubmit="return checkLogin(event);">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?= $product->id; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn btn-primary">🛒 Thêm vào giỏ hàng</button>
                        </form>

                        <script>
                            function checkLogin(event) {
                                var isLoggedIn = <?= json_encode($isLoggedIn); ?>;
                                if (!isLoggedIn) {
                                    event.preventDefault();
                                    alert("Bạn cần đăng nhập để thêm sản phẩm vào giỏ hàng!");
                                    window.location.href = "login.php";
                                }
                            }
                        </script>
                        <?php
                        $canReview = false;

                        if ($isLoggedIn && isset($_SESSION['username'])) {
                            $currentUsername = $_SESSION['username'];
                            $productName = $product->name;

                            $stmt = $PDO->prepare("
                                SELECT COUNT(*) FROM orders 
                                WHERE username = ? AND product_name = ? AND status = 'Đã giao'
                                ");
                            $stmt->execute([$currentUsername, $productName]);
                            $canReview = $stmt->fetchColumn() > 0;
                        }

                        ?>

                        <a href="product.php" class="btn btn-secondary">⬅️ Quay lại</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="container mt-4">
            <!-- Thanh chọn tab -->
            <div class="d-flex gap-2 mb-3">
                <button class="btn btn-primary">Chi tiết sản phẩm</button>
            </div>

            <!-- Tiêu đề -->
            <h5 class="fw-bold">Thông số sản phẩm - <?= htmlspecialchars($product->name); ?></h5>

            <!-- Bảng thông tin -->
            <div class="table-responsive">
                <table class="table border-0">
                    <tbody>
                        <tr class="bg-light">
                            <td class="fw-bold">Thương hiệu: <?= htmlspecialchars($product->brand); ?></td>
                            <td class="fw-bold">Xuất xứ: Nhật</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Kháng nước</td>
                            <td class="fw-bold">5atm</td>
                        </tr>
                        <tr class="bg-light">
                            <td class="fw-bold">Loại máy: Pin/Quartz</td>
                            <td class="fw-bold">Chất liệu kính: Kính khoáng</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Size mặt:</td>
                            <td>35mm</td>
                        </tr>
                    </tbody>
                </table>
            </div>
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
                    <div style="font-size: 1rem;">
                        <span class="text-warning"> <?= str_repeat('⭐', floor($averageRating)); ?> </span>
                        <?php if ($averageRating - floor($averageRating) >= 0.5): ?>
                            <span class="text-warning">★</span>
                        <?php endif; ?>
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
                            <span><?= str_repeat('⭐', $review['rating']); ?></span>
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
            document.getElementById("toggleReviewForm").addEventListener("click", function() {
                document.getElementById("reviewForm").style.display = "block";
                document.getElementById("overlay").style.display = "block";
            });
            document.getElementById("overlay").addEventListener("click", function() {
                document.getElementById("reviewForm").style.display = "none";
                document.getElementById("overlay").style.display = "none";
            });
        </script>

    </main>
</div>

<?php include_once __DIR__ . '/../src/partials/footer.php'; ?>