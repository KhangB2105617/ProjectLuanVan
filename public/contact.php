<?php 
include_once __DIR__ . '/../src/partials/header.php';
?>
<main>
    <div class="container pt-5">
        <div class="row">
            <div class="col-lg-6">
                <h2>Liên hệ với chúng tôi</h2>
                <form action="process_contact.php" method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">Họ và tên</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Nhập họ và tên" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Nhập email" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Lời nhắn</label>
                        <textarea class="form-control" id="message" name="message" rows="4" placeholder="Viết lời nhắn" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Gửi</button>
                </form>
            </div>
            <div class="col-lg-6">
                <h2>Thông tin liên lạc</h2>
                <p><strong>Địa chỉ:</strong>Khu II, Đ. 3 Tháng 2, Xuân Khánh, Ninh Kiều, Cần Thơ, Việt Nam</p>
                <p><strong>Email:</strong> khangb2105617@student.ctu.edu.vn</p>
                <p><strong>Điện thoại:</strong>0900001122</p>
                <h3>Vị trí của chúng tôi</h3>
                <div class="map-responsive">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3928.841454377094!2d105.76804037450879!3d10.029938972519927!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31a0895a51d60719%3A0x9d76b0035f6d53d0!2zxJDhuqFpIGjhu41jIEPhuqduIFRoxqE!5e0!3m2!1svi!2s!4v1732713455694!5m2!1svi!2s" width="100%" height="300" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>
    </div>
</main>
<?php
require_once __DIR__ . '/../src/partials/footer.php';
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
