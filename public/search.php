<?php
require_once __DIR__ . '/../src/bootstrap.php';
use NL\Product;

$product = new Product($PDO);

$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
$products = [];

if ($searchTerm !== '') {
    $products = $product->searchProducts($searchTerm);
}

include_once __DIR__ . '/../src/partials/header.php'; 
?>

<main>
    <div class="container mt-4">

        <?php if ($searchTerm !== ''): ?>
            <h3 class="text-center">Kết quả tìm kiếm cho: "<?= htmlspecialchars($searchTerm); ?>"</h3>
            <div class="row mt-4">
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $product): ?>
                        <div class="col-lg-2 col-md-4 col-sm-6 mb-4 d-flex">
                            <a href="<?= '/product-details.php?id=' . $product->id; ?>" class="text-decoration-none text-dark w-100">
                                <div class="card w-100 shadow-sm border rounded-3">
                                    <img src="/assets/img/<?= htmlspecialchars($product->image); ?>" class="card-img-top" alt="<?= htmlspecialchars($product->name); ?>">
                                    <div class="card-body p-2">
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
                <?php else: ?>
                    <p class="text-center">Không tìm thấy sản phẩm nào phù hợp với từ khóa.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Font Awesome icon -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* Hiệu ứng nhấp nháy */
.listening {
    background-color: red !important;
    color: white !important;
    animation: pulse 1s infinite;
    border-color: red !important;
}
@keyframes pulse {
    0% { box-shadow: 0 0 5px rgba(255,0,0,0.6); }
    50% { box-shadow: 0 0 15px rgba(255,0,0,1); }
    100% { box-shadow: 0 0 5px rgba(255,0,0,0.6); }
}
</style>

<script>
// Voice search
const voiceBtn = document.getElementById('voiceSearchBtn');
const searchInput = document.getElementById('searchInput');

if ('webkitSpeechRecognition' in window) {
    const recognition = new webkitSpeechRecognition();
    recognition.lang = 'vi-VN';
    recognition.continuous = false;
    recognition.interimResults = false;

    voiceBtn.addEventListener('click', () => {
        recognition.start();
        voiceBtn.classList.add('listening'); // thêm hiệu ứng
        voiceBtn.innerHTML = '<i class="fas fa-microphone"></i>';
    });

    recognition.onresult = function(event) {
        const transcript = event.results[0][0].transcript;
        searchInput.value = transcript;
        document.querySelector('form').submit();
    };

    recognition.onerror = function(event) {
        console.error('Voice search error:', event.error);
    };

    recognition.onend = function() {
        voiceBtn.classList.remove('listening'); // bỏ hiệu ứng
        voiceBtn.innerHTML = '<i class="fas fa-microphone"></i>';
    };
} else {
    voiceBtn.style.display = 'none';
}
</script>