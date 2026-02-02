<?php require_once __DIR__ . '/../../layout/head.php'; ?>
<?php require_once __DIR__ . '/../../layout/nav.php'; ?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Sửa mã nguồn</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= url('admin') ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= url('admin/services/codes') ?>">Mã nguồn</a></li>
                        <li class="breadcrumb-item active">Chỉnh sửa</li>
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
                            <h3 class="card-title">Cập nhật mã nguồn: <?= htmlspecialchars($code['title']); ?></h3>
                        </div>
                        <form action="<?= url('admin/services/codes/edit/' . $code['id']) ?>" method="POST">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label>Tên mã nguồn</label>
                                            <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($code['title']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Lượt mua</label>
                                            <input type="number" class="form-control" name="buy" value="<?= $code['buy']; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Giá bán (VNĐ)</label>
                                            <input type="number" class="form-control" name="gia" value="<?= $code['gia']; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Link demo / Link tải</label>
                                            <input type="text" class="form-control" name="link" value="<?= htmlspecialchars($code['link']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Link ảnh đại diện (Thumbnail)</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" name="img" id="code_img" value="<?= htmlspecialchars($code['img']); ?>" required>
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#imageManagerModal" onclick="currentOutputId = 'code_img'">Chọn ảnh</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Danh sách ảnh mô tả (Mỗi dòng 1 link)</label>
                                            <textarea class="form-control" name="list_img" rows="4"><?= htmlspecialchars($code['list_img']); ?></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Trạng thái</label>
                                            <select class="form-control" name="status">
                                                <option value="ON" <?= $code['status'] == 'ON' ? 'selected' : ''; ?>>Hiển thị (ON)</option>
                                                <option value="OFF" <?= $code['status'] == 'OFF' ? 'selected' : ''; ?>>Ẩn (OFF)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Mô tả chi tiết (Nội dung)</label>
                                            <textarea class="textarea" name="noidung" style="width: 100%; height: 300px; font-size: 14px; line-height: 18px; border: 1px solid #dddddd; padding: 10px;"><?= htmlspecialchars($code['noidung']); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-right">
                                <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                                <a href="<?= url('admin/services/codes') ?>" class="btn btn-default">Quay lại</a>
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
