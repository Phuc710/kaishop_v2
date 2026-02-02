<?php require_once __DIR__ . '/../layout/head.php'; ?>
<?php require_once __DIR__ . '/../layout/nav.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Lịch sử nạp bank</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= url('admin') ?>">Home</a></li>
                        <li class="breadcrumb-item active">Nạp bank</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Danh sách giao dịch bank</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="historyTable" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th style="width: 10px">ID</th>
                                            <th>USERNAME</th>
                                            <th>TRANS ID</th>
                                            <th>TYPE</th>
                                            <th>THỰC NHẬN</th>
                                            <th>TRẠNG THÁI</th>
                                            <th>TIME</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($histories)): ?>
                                            <?php foreach ($histories as $row): ?>
                                            <tr>
                                                <td><?= $row['id']; ?></td>
                                                <td><?= htmlspecialchars($row['username']); ?></td>
                                                <td><?= htmlspecialchars($row['trans_id']); ?></td>
                                                <td><?= htmlspecialchars($row['type']); ?></td>
                                                <td><?= number_format($row['thucnhan']); ?>đ</td>
                                                <td>
                                                    <?php 
                                                        $statusClass = 'secondary';
                                                        if ($row['status'] == 'hoantat') $statusClass = 'success';
                                                        if ($row['status'] == 'cho') $statusClass = 'warning';
                                                        if ($row['status'] == 'thatbai') $statusClass = 'danger';
                                                    ?>
                                                    <span class="badge badge-<?= $statusClass ?>"><?= $row['status'] ?></span>
                                                </td>
                                                <td><?= function_exists('ngay') ? ngay($row['time']) : date('H:i:s d/m/Y', $row['time']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
$(document).ready(function() {
    $('#historyTable').DataTable({
        "responsive": true,
        "autoWidth": false,
        "order": [[0, "desc"]]
    });
});
</script>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>
