<?php foreach ($items as $item): ?>
    <?php if (!empty($isSourceHistory)): ?>
        <tr>
            <td class="text-center align-middle">
                <span class="badge bg-light text-dark border font-weight-bold" style="font-family:monospace;">
                    <?= htmlspecialchars($item['order_code_short'] ?: $item['order_code']) ?>
                </span>
            </td>
            <td class="text-center align-middle">
                <?= FormatHelper::eventTime($item['created_at_display'] ?? $item['created_at'], $item['created_at']) ?>
            </td>
            <td class="text-center align-middle">
                <a href="<?= url('admin/users/edit/' . $item['username']) ?>" class="font-weight-bold text-primary">
                    <?= htmlspecialchars($item['username']) ?>
                </a>
            </td>
            <td class="align-middle">
                <?php if ($item['status'] === 'completed'): ?>
                    <div class="d-flex align-items-center">
                        <code class="p-1 px-2 border rounded bg-white text-dark mr-2"
                            style="font-size: 13px; display: inline-block; word-break: break-all;"
                            title="<?= htmlspecialchars($item['stock_content_plain']) ?>"><?= htmlspecialchars($item['stock_content_plain']) ?></code>
                        <button class="btn btn-xs btn-outline-info copy-content-btn"
                            data-content="<?= htmlspecialchars($item['stock_content_plain']) ?>" title="Copy">
                            <i class="far fa-copy"></i>
                        </button>
                    </div>
                <?php else: ?>
                    <span class="text-muted small">—</span>
                <?php endif; ?>
            </td>
            <td class="text-center align-middle">
                <?php if ($item['status'] === 'completed'): ?>
                    <button class="btn btn-info btn-sm btn-fulfill" data-id="<?= $item['id'] ?>"
                        data-code="<?= htmlspecialchars($item['order_code_short'] ?: $item['order_code']) ?>"
                        data-input="<?= htmlspecialchars($item['customer_input'] ?: '') ?>"
                        data-content="<?= htmlspecialchars($item['stock_content_plain'] ?: '') ?>" title="Sửa nội dung (Bảo hành)">
                        <i class="fas fa-edit mr-1"></i> Sửa
                    </button>
                    <button class="btn btn-danger btn-sm btn-cancel-order ml-1" data-id="<?= $item['id'] ?>"
                        data-code="<?= htmlspecialchars($item['order_code_short'] ?: $item['order_code']) ?>"
                        title="Hủy đơn + Hoàn tiền">
                        <i class="fas fa-times mr-1"></i> Hủy
                    </button>
                <?php elseif ($item['status'] === 'pending'): ?>
                    <button class="btn btn-success btn-sm btn-fulfill" data-id="<?= $item['id'] ?>"
                        data-code="<?= htmlspecialchars($item['order_code_short'] ?: $item['order_code']) ?>"
                        data-input="<?= htmlspecialchars($item['customer_input'] ?: '') ?>" title="Giao đơn (Gửi thông tin)">
                        <i class="fas fa-paper-plane mr-1"></i> Giao
                    </button>
                    <button class="btn btn-danger btn-sm btn-cancel-order ml-1" data-id="<?= $item['id'] ?>"
                        data-code="<?= htmlspecialchars($item['order_code_short'] ?: $item['order_code']) ?>"
                        title="Hủy đơn + Hoàn tiền">
                        <i class="fas fa-times mr-1"></i> Hủy
                    </button>
                <?php else: ?>
                    <span class="badge badge-danger">Đã hủy</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php else: ?>
        <tr>
            <td class="text-center align-middle">
                <span class="badge bg-light text-dark border font-weight-bold" style="font-family:monospace;">
                    <?= htmlspecialchars($item['order_code_short'] ?: $item['order_code']) ?>
                </span>
            </td>
            <td class="text-center align-middle">
                <a href="<?= url('admin/users/edit/' . $item['username']) ?>" class="font-weight-bold text-primary">
                    <?= htmlspecialchars($item['username']) ?>
                </a>
            </td>
            <td class="align-middle">
                <div class="mb-1" style="word-break: break-all;">
                    <?= htmlspecialchars($item['customer_input'] ?: 'N/A') ?>
                </div>
                <div class="text-muted small">
                    <i class="far fa-clock mr-1"></i>
                    <span class="badge bg-light text-dark border-0 p-0" style="font-size: 11px;">
                        Đặt:
                        <?= FormatHelper::eventTime($item['created_at_display'] ?? $item['created_at'], $item['created_at']) ?>
                    </span>
                </div>
            </td>
            <td class="align-middle">
                <?php if ($item['status'] === 'completed'): ?>
                    <div class="mb-1" style="word-break: break-all;">
                        <code
                            class="p-0 bg-transparent text-success font-weight-bold"><?= htmlspecialchars($item['stock_content_plain'] ?: '—') ?></code>
                    </div>
                    <?php if (!empty($item['fulfilled_at'])): ?>
                        <div class="text-muted small">
                            <i class="fas fa-check-circle text-success mr-1"></i>
                            <span class="badge bg-light text-success border-0 p-0" style="font-size: 11px;">
                                Giao:
                                <?= FormatHelper::eventTime($item['fulfilled_at_display'] ?? $item['fulfilled_at'], $item['fulfilled_at']) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="text-muted small italic">Chưa bàn giao</span>
                <?php endif; ?>
            </td>
            <td class="text-center align-middle">
                <?php
                $st = $item['status'];
                if ($st === 'pending')
                    echo '<span class="badge badge-warning">Pending</span>';
                elseif ($st === 'completed')
                    echo '<span class="badge badge-success">Xong</span>';
                else
                    echo '<span class="badge badge-danger">Hủy</span>';
                ?>
            </td>
            <td class="text-center align-middle">
                <?php if ($item['status'] === 'pending'): ?>
                    <button class="btn btn-success btn-sm btn-fulfill" data-id="<?= $item['id'] ?>"
                        data-code="<?= htmlspecialchars($item['order_code_short'] ?: $item['order_code']) ?>"
                        data-input="<?= htmlspecialchars($item['customer_input'] ?: '') ?>" title="Giao đơn (Gửi thông tin)">
                        <i class="fas fa-paper-plane mr-1"></i> Giao
                    </button>
                    <button class="btn btn-danger btn-sm btn-cancel-order ml-1" data-id="<?= $item['id'] ?>"
                        data-code="<?= htmlspecialchars($item['order_code_short'] ?: $item['order_code']) ?>"
                        title="Hủy đơn + Hoàn tiền">
                        <i class="fas fa-times mr-1"></i> Hủy
                    </button>
                <?php elseif ($item['status'] === 'completed'): ?>
                    <button class="btn btn-info btn-sm btn-fulfill" data-id="<?= $item['id'] ?>"
                        data-code="<?= htmlspecialchars($item['order_code_short'] ?: $item['order_code']) ?>"
                        data-input="<?= htmlspecialchars($item['customer_input'] ?: '') ?>"
                        data-content="<?= htmlspecialchars($item['stock_content_plain'] ?: '') ?>" title="Sửa nội dung (Bảo hành)">
                        <i class="fas fa-edit mr-1"></i> Sửa
                    </button>
                <?php else: ?>
                    <button class="btn btn-light btn-sm text-muted" disabled>
                        <i class="fas fa-check"></i>
                    </button>
                <?php endif; ?>
            </td>
        </tr>
    <?php endif; ?>
<?php endforeach; ?>