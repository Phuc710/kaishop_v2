<?php foreach ($items as $item): ?>
    <tr id="stock-row-<?= $item['id'] ?>" class="<?= $item['status'] === 'sold' ? 'table-light' : '' ?>">
        <td class="text-center align-middle">
            <?php if (!empty($item['order_code'])): ?>
                <span class="badge bg-light text-dark border font-weight-bold" style="font-family:monospace;">
                    <?= htmlspecialchars(substr($item['order_code'], 0, 8)) ?>
                </span>
            <?php else: ?>
                <span class="text-muted small">—</span>
            <?php endif; ?>
        </td>
        <td class="align-middle">
            <div class="d-flex align-items-center">
                <code class="p-1 px-2 border rounded bg-white text-dark mr-2"
                    style="font-size: 14px;"><?= htmlspecialchars($item['content']) ?></code>
                <button class="btn btn-xs btn-outline-info copy-content-btn"
                    data-content="<?= htmlspecialchars($item['content']) ?>" title="Copy">
                    <i class="far fa-copy"></i>
                </button>
            </div>
            <?php if ($item['status'] === 'sold' && $item['sold_at']): ?>
                <div class="mt-1 small">
                    <span class="text-danger font-weight-bold"><i class="far fa-clock mr-1"></i>Bán lúc:</span>
                    <span class="text-muted">
                        <?= FormatHelper::eventTime($item['sold_at_display'] ?? $item['sold_at'], $item['sold_at']) ?>
                    </span>
                </div>
            <?php endif; ?>
        </td>
        <td class="text-center align-middle">
            <?php if (!empty($item['buyer_username'])): ?>
                <a href="<?= url('admin/users/edit/' . $item['buyer_username']) ?>"
                    class="d-inline-flex align-items-center text-primary font-weight-bold">
                    <?= htmlspecialchars($item['buyer_username']) ?>
                </a>
            <?php else: ?>
                <span class="text-muted small"><i class="fas fa-minus mr-1"></i>Chưa bán</span>
            <?php endif; ?>
        </td>
        <td class="text-center align-middle">
            <?php if ($item['status'] === 'available'): ?>
                <span class="badge badge-success px-2 py-1">CÒN HÀNG</span>
            <?php else: ?>
                <span class="badge badge-secondary px-2 py-1">ĐÃ BÁN</span>
            <?php endif; ?>
        </td>
        <td class="text-center align-middle">
            <?= FormatHelper::eventTime($item['created_at_display'] ?? ($item['created_at'] ?? ''), $item['created_at'] ?? '') ?>
        </td>
        <td class="text-center align-middle">
            <button class="btn btn-info btn-sm edit-stock-btn" data-id="<?= $item['id'] ?>"
                data-content="<?= htmlspecialchars($item['content']) ?>"
                title="<?= $item['status'] === 'available' ? 'Sửa nội dung' : 'Sửa nội dung (Bảo hành)' ?>">
                <i class="fas fa-edit mr-1"></i> Sửa
            </button>
            <?php if ($item['status'] === 'available'): ?>
                <button class="btn btn-danger btn-sm ml-1 delete-stock-btn" data-id="<?= $item['id'] ?>" title="Xóa">
                    <i class="fas fa-trash mr-1"></i> Xóa
                </button>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; ?>