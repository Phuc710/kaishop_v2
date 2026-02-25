<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title><?= htmlspecialchars((string) ($product['name'] ?? 'Sản phẩm')) ?> | <?= htmlspecialchars((string) ($chungapi['ten_web'] ?? 'KaiShop')) ?></title>
</head>

<body>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>

    <main>
        <div class="breadcrumb-bar breadcrumb-bar-info">
            <div class="container">
                <div class="row">
                    <div class="col-md-12 col-12">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="<?= url('') ?>">Trang chủ</a></li>
                                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars((string) ($product['name'] ?? '')) ?></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container">
                <div class="row">
                    <div class="col-md-5">
                        <div class="card">
                            <div class="card-body text-center">
                                <img src="<?= htmlspecialchars((string) ($product['image'] ?? '')) ?>" class="img-fluid rounded"
                                    alt="<?= htmlspecialchars((string) ($product['name'] ?? '')) ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-body">
                                <h2><?= htmlspecialchars((string) ($product['name'] ?? '')) ?></h2>
                                <h3 class="text-primary"><?= number_format((int) ($product['price_vnd'] ?? 0)); ?>đ</h3>
                                <hr>
                                <h4>Mô tả</h4>
                                <div class="product-description">
                                    <?= nl2br(htmlspecialchars((string) ($product['description'] ?? ''))) ?>
                                </div>
                                <hr>
                                <div class="form-group">
                                    <button class="btn btn-lg btn-success btn-block"
                                        onclick="buyProduct(<?= (int) ($product['id'] ?? 0); ?>)">
                                        <i class="fas fa-shopping-cart"></i> Mua ngay
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>

    <script>
        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function buyProduct(id) {
            fetch('<?= url('product') ?>/' + encodeURIComponent(id) + '/purchase', {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store'
            })
                .then(async function (res) {
                    let data = {};
                    try { data = await res.json(); } catch (e) { }
                    if (!res.ok || !data.success) {
                        throw new Error((data && data.message) ? data.message : 'Khong the mua san pham luc nay.');
                    }
                    return data;
                })
                .then(function (data) {
                    var order = data.order || {};
                    var html = ''
                        + '<div style="text-align:left">'
                        + '<p><b>Mã đơn:</b> ' + escapeHtml(order.order_code || '-') + '</p>'
                        + '<p><b>Sản phẩm:</b> ' + escapeHtml(order.product_name || '-') + '</p>'
                        + '<p><b>Giá:</b> ' + new Intl.NumberFormat('vi-VN').format(Number(order.price || 0)) + 'đ</p>'
                        + '<hr>'
                        + '<p><b>Dữ liệu bàn giao:</b></p>'
                        + '<textarea readonly style="width:100%;min-height:150px;border:1px solid #ddd;border-radius:8px;padding:10px;">'
                        + escapeHtml(order.content || '')
                        + '</textarea>'
                        + '</div>';

                    if (window.Swal && Swal.fire) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Thanh toán thành công!',
                            html: html,
                            width: 760,
                            confirmButtonText: 'Đóng'
                        });
                    } else if (typeof swal === 'function') {
                        swal('Thành công', data.message || 'Thanh toán thành công!', 'success');
                    } else {
                        alert(data.message || 'Thanh toán thành công!');
                    }
                })
                .catch(function (err) {
                    var msg = (err && err.message) ? err.message : 'Khong the mua san pham luc nay.';
                    if (/dang nhap/i.test(msg)) {
                        window.location.href = '<?= url('login') ?>';
                        return;
                    }
                    if (window.Swal && Swal.fire) {
                        Swal.fire({ icon: 'error', title: 'Thất bại', text: msg });
                    } else if (typeof swal === 'function') {
                        swal('Thất bại', msg, 'error');
                    } else {
                        alert(msg);
                    }
                });
        }
    </script>
</body>

</html>
