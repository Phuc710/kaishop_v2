<?php require_once __DIR__ . '/../../layout/head.php'; ?>
<?php require_once __DIR__ . '/../../layout/nav.php'; ?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Thêm mẫu logo</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= url('admin') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= url('admin/services/logos') ?>">Logo</a></li>
                        <li class="breadcrumb-item active">Thêm mới</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Cấu hình logo mới</h3>
                        </div>
                        <form action="<?= url('admin/services/logos/add') ?>" method="POST">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Tên logo</label>
                                            <input type="text" class="form-control" name="title" placeholder="Ví dụ: Logo phong cách gaming" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Giá bán (VNĐ)</label>
                                            <input type="number" class="form-control" name="gia" placeholder="150000" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Trạng thái</label>
                                            <select class="form-control" name="status">
                                                <option value="ON">Hiển thị (ON)</option>
                                                <option value="OFF">Ẩn (OFF)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Link ảnh đại diện (Thumbnail)</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" name="img" id="logo_img" required>
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#imageManagerModal" onclick="currentOutputId = 'logo_img'">Chọn ảnh</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Danh sách ảnh mô tả (Mỗi dòng 1 link)</label>
                                            <textarea class="form-control" name="list_img" rows="5" placeholder="https://image1.jpg\nhttps://image2.jpg"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button type="submit" class="btn btn-primary">Thêm ngay</button>
                                <a href="<?= url('admin/services/logos') ?>" class="btn btn-default">Quay lại</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include_once ROOT_PATH . '/admin/image-manager-modal.php'; ?>
<?php require_once __DIR__ . '/../../layout/foot.php'; ?>
