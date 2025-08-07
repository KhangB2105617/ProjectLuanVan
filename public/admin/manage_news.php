<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header('Location: /unauthorized.php');
    exit;
}

require_once __DIR__ . '/../../src/bootstrap.php';
use NL\ProductNews;

$newsModel = new ProductNews($PDO);
$keyword = $_GET['keyword'] ?? null;

if ($keyword) {
    $newsList = $newsModel->searchNews($keyword);
} else {
    $newsList = $newsModel->getAll();
}

$pageTitle = "Quản lý tin tức";
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?>

        <main class="col-md-10 ms-sm-auto px-md-4" style="margin-left: 17%;">
            <div class="pt-4">
                <h1 class="mb-4">Quản lý tin tức</h1>

                <form class="row g-3 mb-3" method="GET" style="max-width: 400px;">
                    <div class="col-8">
                        <input type="text" name="keyword" class="form-control form-control-sm" placeholder="Tìm bài viết..." value="<?= htmlspecialchars($keyword ?? '') ?>">
                    </div>
                    <div class="col-3">
                        <button type="submit" class="btn btn-sm btn-primary w-100">Tìm kiếm</button>
                    </div>
                </form>

                <a href="add_news.php" class="btn btn-success mb-3"><i class="fas fa-plus"></i> Thêm tin tức</a>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Hình ảnh</th>
                                <th>ID</th>
                                <th>Tiêu đề</th>
                                <th>Tóm tắt</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($newsList)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Không tìm thấy bài viết nào.</td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($newsList as $news): ?>
                                <tr>
                                    <td><img src="/assets/img/<?= htmlspecialchars($news->image); ?>" style="max-width: 100px;" alt="<?= htmlspecialchars($news->title); ?>"></td>
                                    <td><?= $news->id ?></td>
                                    <td><?= htmlspecialchars($news->title) ?></td>
                                    <td><?= htmlspecialchars(mb_strimwidth($news->summary, 0, 100, '...')) ?></td>
                                    <td>
                                        <a href="edit_news.php?id=<?= $news->id ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Sửa</a>
                                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?= $news->id ?>">
                                            <i class="fas fa-trash"></i> Xóa
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal xác nhận xóa -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="deleteForm" method="post" action="delete_news.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Xác nhận xóa bài viết</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    Bạn có chắc chắn muốn xóa bài viết này?
                </div>
                <input type="hidden" name="id" id="deleteNewsId">
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger">Xóa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var deleteModal = document.getElementById('deleteModal');
        deleteModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var newsId = button.getAttribute('data-id');
            document.getElementById('deleteNewsId').value = newsId;
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>