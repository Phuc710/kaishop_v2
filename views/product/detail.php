<!DOCTYPE html>
<html lang="en">

<head>
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title><?= $product['name']; ?> | <?= $chungapi['ten_web']; ?></title>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>
</head>

<body>
    <main>
        <div class="breadcrumb-bar breadcrumb-bar-info">
            <div class="container">
                <div class="row">
                    <div class="col-md-12 col-12">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="/">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page"><?= $product['name']; ?></li>
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
                                <img src="<?= $product['image']; ?>" class="img-fluid rounded" alt="<?= $product['name']; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-body">
                                <h2><?= $product['name']; ?></h2>
                                <h3 class="text-primary"><?= number_format($product['price']); ?>đ</h3>
                                <hr>
                                <h4>Mô tả</h4>
                                <div class="product-description">
                                    <?= nl2br($product['description']); ?>
                                </div>
                                <hr>
                                <div class="form-group">
                                    <button class="btn btn-lg btn-success btn-block" onclick="buyProduct(<?= $product['id']; ?>)">
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
        function buyProduct(id) {
            swal("Thông báo", "Chức năng mua hàng đang được phát triển!", "info");
            // Implement purchase logic here later
        }
    </script>
</body>

</html>
