<?php require_once __DIR__ . '/../../layout/head.php'; ?>
<?php
$pageTitle = 'Danh sách mẫu logo';
$breadcrumbs = [
    ['label' => 'Dịch vụ'],
    ['label' => 'Mẫu Logo', 'url' => url('admin/services/logos')],
    ['label' => 'Danh sách'],
];
require_once __DIR__ . '/../../layout/breadcrumb.php';
?>

<section class="content pb-4 mt-3">
    <div class="container-fluid">
        <div class="card custom-card">
            <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                <h3 class="card-title text-uppercase font-weight-bold">DANH SÁCH MẪU LOGO</h3>
                <div class="card-tools">
                    <a class="btn btn-primary btn-sm" href="<?= url('admin/services/logos/add') ?>">
                        <i class="fas fa-plus-circle mr-1"></i>Thêm Logo
                    </a>
                </div>
            </div>
            <div class="card-body pt-3">
                <div class="table-responsive table-wrapper mb-3">
                    <table id="logoTable" class="table text-nowrap table-hover table-bordered admin-table">
                        <thead>
                            <tr>
                                <th class="text-center font-weight-bold align-middle" style="width:50px">ID</th>
                                <th class="text-center font-weight-bold align-middle" style="width:110px">ẢNH</th>
                                <th class="text-center font-weight-bold align-middle">TÊN LOGO</th>
                                <th class="text-center font-weight-bold align-middle">TRẠNG THÁI</th>
                                <th class="text-center font-weight-bold align-middle">GIÁ TIỀN</th>
                                <th class="text-center font-weight-bold align-middle" style="width:120px">THAO TÁC</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($logos)): ?>
                                <?php foreach ($logos as $row): ?>
                                    <tr>
                                        <td class="text-center align-middle"><?= $row['id']; ?></td>
                                        <td class="text-center align-middle">
                                            <img src="<?= htmlspecialchars($row['img']); ?>"
                                                alt="<?= htmlspecialchars($row['title']); ?>"
                                                style="width:80px;height:auto;border-radius:5px;box-shadow:0 0 5px rgba(0,0,0,0.1);">
                                        </td>
                                        <td class="text-center align-middle font-weight-bold">
                                            <?= htmlspecialchars($row['title']); ?>
                                        </td>
                                        <td class="text-center align-middle">
                                            <?php if ($row['status'] == 'ON'): ?>
                                                <span class="badge badge-success">HIỂN THỊ</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">ẨN</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center align-middle font-weight-bold text-danger">
                                            <?= number_format($row['gia']); ?>đ
                                        </td>
                                        <td class="text-center align-middle">
                                            <div class="btn-group">
                                                <a href="<?= url('admin/services/logos/edit/' . $row['id']) ?>"
                                                    class="btn btn-search-dt btn-sm" title="Sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?= url('admin/services/logos?delete=' . $row['id']) ?>"
                                                    class="btn btn-danger btn-sm ml-1" title="Xóa"
                                                    onclick="event.preventDefault(); SwalHelper.confirmDelete(() => { window.location.href = this.href; })">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Không có dữ liệu</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../../layout/foot.php'; ?>

<script>
    $(document).ready(function () {
        $('#logoTable').DataTable({
            responsive: true,
            autoWidth: false,
            order: [[0, 'desc']],
            language: {
                sLengthMenu: 'Hiển thị _MENU_ mục',
                sZeroRecords: 'Không tìm thấy dữ liệu',
                sInfo: 'Xem _START_–_END_ / _TOTAL_ mục',
                sInfoEmpty: 'Không có dữ liệu',
                sSearch: 'Tìm kiếm:',
                oPaginate: { sFirst: 'Đầu', sPrevious: '‹', sNext: '›', sLast: 'Cuối' },
            },
        });
    });
</script>