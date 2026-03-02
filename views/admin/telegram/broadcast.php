<?php
/**
 * View: Telegram Bot — Broadcast
 * Route: GET /admin/telegram/broadcast
 */
require_once __DIR__ . '/../layout/head.php';
$pageTitle = 'Telegram Bot — Gửi thông báo';
$breadcrumbs = [
    ['label' => 'Telegram Bot', 'url' => url('admin/telegram')],
    ['label' => 'Gửi thông báo toàn user'],
];
require_once __DIR__ . '/../layout/breadcrumb.php';
?>

<section class="content mt-3">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-transparent border-0 py-4">
                        <h3 class="card-title font-weight-bold text-uppercase">
                            <i class="fas fa-bullhorn mr-2 text-primary"></i> Gửi thông báo toàn hệ thống
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="alert bg-soft-info border-info-light mb-4">
                            <i class="fas fa-info-circle mr-2 text-info"></i>
                            Thông báo sẽ được gửi tới <b>tất cả người dùng đã liên kết Telegram</b> với tài khoản
                            KaiShop.
                            Tin nhắn sẽ được đưa vào hàng đợi (Outbox) và gửi đi lần lượt.
                        </div>

                        <form id="broadcastForm">
                            <div class="form-group">
                                <label class="font-weight-bold ml-1">Nội dung tin nhắn <span
                                        class="text-danger">*</span></label>
                                <textarea name="message" class="form-control" rows="10"
                                    placeholder="Nhập nội dung tin nhắn gửi tới khách hàng... (Hỗ trợ HTML: <b>, <i>, <code>, <a>...)"
                                    required></textarea>
                                <small class="text-muted mt-2 d-block">
                                    <i class="fas fa-lightbulb mr-1"></i> Mẹo: Sử dụng
                                    <code>&lt;b&gt;văn bản&lt;/b&gt;</code> để in đậm,
                                    <code>&lt;a href="url"&gt;link&lt;/a&gt;</code> để chèn liên kết.
                                </small>
                            </div>

                            <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                                <a href="<?= url('admin/telegram') ?>" class="btn btn-light px-4">
                                    <i class="fas fa-arrow-left mr-1"></i> Quay lại
                                </a>
                                <button type="submit" class="btn btn-primary px-5 shadow-sm font-weight-bold">
                                    <i class="fas fa-paper-plane mr-1"></i> Bắt đầu gửi ngay
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('broadcastForm');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;

            Swal.fire({
                title: 'Xác nhận gửi?',
                text: "Bạn có chắc chắn muốn gửi thông báo này tới tất cả người dùng liên kết Telegram?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Đồng ý, gửi ngay!',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Đang xử lý...';

                    // Using jQuery here is fine because DOMContentLoaded ensures scripts are loaded if they are in head,
                    // but if jQuery is in foot.php, we might still have a race. 
                    // To be safe, let's use fetch instead of $.ajax if possible, or check if jQuery exists.

                    const formData = new FormData(form);
                    fetch('<?= url('admin/telegram/broadcast') ?>', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(res => {
                            if (res.success) {
                                Swal.fire('Thành công!', res.message, 'success').then(() => {
                                    window.location.href = '<?= url('admin/telegram/outbox') ?>';
                                });
                            } else {
                                Swal.fire('Lỗi!', res.message || 'Không thể thực hiện.', 'error');
                                btn.disabled = false;
                                btn.innerHTML = originalText;
                            }
                        })
                        .catch(() => {
                            Swal.fire('Lỗi!', 'Lỗi kết nối máy chủ.', 'error');
                            btn.disabled = false;
                            btn.innerHTML = originalText;
                        });
                }
            });
        });
    });
</script>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>