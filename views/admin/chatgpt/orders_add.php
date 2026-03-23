<?php
$pageTitle = 'Thêm đơn hàng GPT';
$breadcrumbs = [
    ['label' => 'GPT Business', 'url' => url('admin/gpt-business/farms')],
    ['label' => 'Đơn hàng GPT', 'url' => url('admin/gpt-business/orders')],
    ['label' => 'Thêm đơn hàng'],
];
require __DIR__ . '/../layout/head.php';
require __DIR__ . '/../layout/breadcrumb.php';

$farms = $farms ?? [];
$error = $error ?? null;
?>

<style>
    .admin-chatgpt-page .content-header {
        display: none;
    }

    .gptb-card-header-main {
        background: #fff !important;
        color: #212529 !important;
        border-bottom: 1px solid #ebedf2 !important;
        padding: 15px 20px !important;
    }

    .gptb-card-header {
        background: #fff !important;
        color: #212529 !important;
        border-bottom: 1px solid #ebedf2 !important;
        padding: 15px 20px !important;
    }

    .gptb-title-with-bar {
        border-left: 4px solid #6610f2;
        padding-left: 12px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .btn-gptb-save {
        background-color: #ffc107 !important;
        color: #000 !important;
        border: none !important;
        font-weight: 800 !important;
        border-radius: 8px !important;
        transition: all 0.3s ease;
    }

    .form-label-req::after {
        content: " *";
        color: #dc3545;
    }

    .gptb-filter-label {
        font-weight: 700;
        color: #475569;
        font-size: 13px;
        text-transform: uppercase;
        margin-bottom: 8px;
        display: block;
    }
</style>

<section class="content pb-4 mt-3">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card custom-card">
                    <div class="card-header gptb-card-header">
                        <span class="gptb-title-with-bar">TẠO ĐƠN HÀNG GPT MỚI</span>
                    </div>

                    <div class="card-body pt-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <?= htmlspecialchars($error) ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        <?php endif; ?>

                        <form action="<?= url('admin/gpt-business/orders/add') ?>" method="POST" id="orderForm">
                            <div class="form-group mb-4">
                                <label class="gptb-filter-label" for="customer_email">Email khách hàng <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    </div>
                                    <input type="email" name="customer_email" id="customer_email" class="form-control"
                                        placeholder="Nhập email ChatGPT của khách..." required>
                                </div>
                                <small class="text-muted">Đơn hàng sẽ được tạo ở trạng thái Chờ xử lý (Pending).</small>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label class="gptb-filter-label" for="assigned_farm_id">Chỉ định Farm (Tùy
                                            chọn)</label>
                                        <select name="assigned_farm_id" id="assigned_farm_id" class="form-control">
                                            <option value="0">-- Tự động điều phối --</option>
                                            <?php foreach ($farms as $farm): ?>
                                                <option value="<?= (int) $farm['id'] ?>">
                                                    <?= htmlspecialchars($farm['farm_name']) ?> (
                                                    <?= htmlspecialchars($farm['admin_email']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-4">
                                        <label class="gptb-filter-label" for="months">Thời hạn (Tháng)</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i
                                                        class="fas fa-calendar-alt"></i></span>
                                            </div>
                                            <input type="number" name="months" id="months" class="form-control"
                                                value="1" min="1" max="120">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label class="gptb-filter-label" for="note">Ghi chú nội bộ</label>
                                <textarea name="note" id="note" class="form-control" rows="3"
                                    placeholder="Ghi chú về đơn hàng này..."></textarea>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-gptb-save px-5">
                                    <i class="fas fa-save mr-1"></i> TẠO ĐƠN HÀNG
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../layout/foot.php'; ?>