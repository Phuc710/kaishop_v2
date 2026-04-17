<?php
/**
 * View: Admin Goals — Mục tiêu tài chính (REFACOR PREMIUM)
 * Route: GET /admin/goals
 */
$pageTitle  = 'Mục tiêu tài chính';
$breadcrumbs = [
    ['label' => 'Quản lý', 'url' => url('admin/users')],
    ['label' => 'Mục tiêu tài chính'],
];
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/breadcrumb.php';

$goals        = is_array($goals        ?? null) ? $goals        : [];
$stats        = is_array($stats        ?? null) ? $stats        : [];
$statusFilter = (string) ($statusFilter ?? 'all');

$fmtMoney = static fn($v): string => number_format((int) $v) . 'đ';
$fmtInt   = static fn($v): string => number_format((int) $v);

$totalTarget  = (int) ($stats['total_target']  ?? 0);
$totalCurrent = (int) ($stats['total_current'] ?? 0);
$overallPct   = $totalTarget > 0 ? min(100, round(($totalCurrent / $totalTarget) * 100)) : 0;
?>

<style>
/* Specific animations & overrides for Goals page */
.goal-progress-fill { height: 100%; border-radius: 99px; transition: width .6s ease; }
.goal-progress-fill.is-hot { background: linear-gradient(90deg, #f59e0b, #ef4444); }
.goal-progress-fill.is-done { background: linear-gradient(90deg, #10b981, #34d399); }

.goal-actions .goal-btn {
    height: 32px; padding: 0 12px; border-radius: 8px; border: 1px solid #e2e8f0;
    font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex;
    align-items: center; gap: 4px; transition: all .15s; background: #fff;
}
.goal-actions .goal-btn:hover { border-color: #2563eb; color: #2563eb; background: #f0f7ff; }

/* Detail Panel Overrides */
.goal-detail-panel { border-radius: 14px; border: 1px solid #e2e8f0; background: #fff; box-shadow: 0 8px 24px rgba(15,23,42,0.05); overflow: hidden; position: sticky; top: 20px; }
.goal-detail-header { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; padding: 16px 20px; display: flex; align-items: center; gap: 10px; }
.goal-tab-btn { flex: 1; padding: 12px; border: none; background: #f8fafc; font-size: 13px; font-weight: 700; color: #64748b; cursor: pointer; border-bottom: 2px solid transparent; }
.goal-tab-btn.active { color: #2563eb; border-bottom-color: #2563eb; background: #fff; }

.goal-note-area { width: 100%; min-height: 200px; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; font-size: 14px; line-height: 1.6; background: #fcfdfe; }

/* AI Insight Boxes */
.insight-box { display: flex; gap: 12px; padding: 14px; border-radius: 12px; margin-bottom: 12px; border: 1px solid transparent; font-size: 13.5px; line-height: 1.5; }
.insight-box i { font-size: 18px; margin-top: 2px; }
.insight-box--info { background: #f0f9ff; border-color: #bae6fd; color: #0369a1; }
.insight-box--success { background: #f0fdf4; border-color: #bbf7d0; color: #15803d; }
.insight-box--warning { background: #fffbeb; border-color: #fef3c7; color: #b45309; }
.insight-box--danger { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }

/* Blue Button Override */
.btn-goal-primary {
    background-color: #2563eb !important;
    border-color: #2563eb !important;
    color: #fff !important;
}
.btn-goal-primary:hover {
    background-color: #1d4ed8 !important;
    border-color: #1d4ed8 !important;
}

/* Premium Transaction Item */
.tx-item {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 16px; margin-bottom: 10px; border-radius: 12px;
    background: #f8fafc; border: 1px solid #f1f5f9; transition: all .2s;
}
.tx-item:hover { background: #fff; box-shadow: 0 4px 12px rgba(15,23,42,0.08); border-color: #e2e8f0; transform: translateY(-1px); }
.tx-icon { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; }
.tx-icon--add { background: #ecfdf5; color: #10b981; }
.tx-icon--sub { background: #fef2f2; color: #ef4444; }
.tx-action-btn { width: 28px; height: 28px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all .15s; background: #fff; border: 1px solid #e2e8f0; color: #64748b; }
.tx-action-btn:hover { background: #f1f5f9; color: #0f172a; }
.tx-action-btn.edit:hover { color: #2563eb; border-color: #2563eb; }
.tx-action-btn.del:hover { color: #ef4444; border-color: #ef4444; }
</style>

<section class="content pb-5 mt-5 admin-goals-page">
    <div class="container-fluid">

        <!-- ── Premium Stats Bar (Match GPT Style) ──────────────── -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="goal-stat-card goal-stat-card--primary">
                    <div class="goal-stat-icon"><i class="fas fa-bullseye"></i></div>
                    <div class="goal-stat-body">
                        <div class="goal-stat-label">Tổng mục tiêu</div>
                        <div class="goal-stat-value" id="kpi-total"><?= $fmtInt($stats['total'] ?? 0) ?></div>
                        <div class="goal-stat-hint">Mục tiêu đã tạo</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="goal-stat-card goal-stat-card--success">
                    <div class="goal-stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="goal-stat-body">
                        <div class="goal-stat-label">Hoàn thành</div>
                        <div class="goal-stat-value" id="kpi-completed"><?= $fmtInt($stats['completed'] ?? 0) ?></div>
                        <div class="goal-stat-hint">Mục tiêu về đích</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="goal-stat-card goal-stat-card--warning">
                    <div class="goal-stat-icon"><i class="fas fa-coins"></i></div>
                    <div class="goal-stat-body">
                        <div class="goal-stat-label">Đã tiết kiệm</div>
                        <div class="goal-stat-value" id="kpi-saved" style="font-size: 1.25rem"><?= $fmtMoney($totalCurrent) ?></div>
                        <div class="goal-stat-hint">Số tiền tích lũy</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="goal-stat-card goal-stat-card--info">
                    <div class="goal-stat-icon"><i class="fas fa-flag-checkered"></i></div>
                    <div class="goal-stat-body">
                        <div class="goal-stat-label">Tổng đích đến</div>
                        <div class="goal-stat-value" id="kpi-target" style="font-size: 1.25rem"><?= $fmtMoney($totalTarget) ?></div>
                        <div class="goal-stat-hint">Kế hoạch tài chính</div>
                    </div>
                </div>
            </div>
        </div>



        <!-- ── Main Layout: LEFT list / RIGHT detail ─────────────── -->
        <div class="row mt-5">
            <!-- LEFT — Goal Cards -->
            <div class="col-xl-7">
                
                <div class="card custom-card">
                    <div class="card-header goal-card-header">
                        <div class="row align-items-center w-100 mx-0">
                            <div class="col-md-6 col-12 px-0">
                                <span class="goal-title-with-bar">DANH SÁCH MỤC TIÊU</span>
                            </div>
                            <div class="col-md-6 col-12 px-0 text-md-right text-left mt-2 mt-md-0">
                                <button onclick="GoalsApp.openCreateModal()" class="btn btn-goal-primary btn-sm rounded-pill px-4 font-weight-bold">
                                    <i class="fas fa-plus-circle mr-1"></i> Tạo mục tiêu
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="dt-filters bg-light border-bottom px-4 py-3">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <span class="small font-weight-bold text-muted mr-2">LỌC:</span>
                            <button class="btn btn-sm <?= $statusFilter==='all'?'btn-goal-primary':'btn-light border' ?> rounded-pill px-3" onclick="GoalsApp.filter('all')">Tất cả</button>
                            <button class="btn btn-sm <?= $statusFilter==='completed'?'btn-goal-primary':'btn-light border' ?> rounded-pill px-3" onclick="GoalsApp.filter('completed')">Hoàn thành</button>
                        </div>
                    </div>

                    <div class="card-body p-5" id="goals-cards-wrap">
                        <?php if (empty($goals)): ?>
                        <div class="goal-empty-state">
                            <div class="goal-empty-icon"><i class="fas fa-piggy-bank"></i></div>
                            <h5 class="font-weight-bold text-dark mb-1">Chưa có mục tiêu nào!</h5>
                            <p class="text-muted small mb-4">Hãy tạo mục tiêu đầu tiên để bắt đầu quản lý.</p>
                        </div>
                        <?php else: ?>
                            <?php foreach ($goals as $goal):
                                $pct    = (float) ($goal['percent']   ?? 0);
                                $isHot  = (bool)  ($goal['is_hot']    ?? false);
                                $isDone = ($goal['status'] ?? '') === 'completed';
                                $gId    = (int) $goal['id'];
                            ?>
                            <div class="goal-item-card <?= $isDone?'border-success':'' ?>" id="goal-card-<?= $gId ?>" data-goal-id="<?= $gId ?>" onclick="GoalsApp.selectGoal(<?= $gId ?>, this)">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="goal-emoji-box bg-light rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:52px; height:52px; font-size: 24px; min-width: 52px">
                                        <?= htmlspecialchars($goal['emoji'] ?? '🎯') ?>
                                    </div>
                                    <div class="flex-grow-1 min-width-0">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <h6 class="font-weight-bold mb-0 text-truncate mr-2"><?= htmlspecialchars($goal['name'] ?? '—') ?></h6>
                                            <div class="d-flex gap-1">
                                                <?php if($isDone): ?>
                                                    <span class="badge badge-success rounded-pill px-3 shadow-sm">Xong</span>
                                                <?php elseif($isHot): ?>
                                                    <span class="badge badge-danger rounded-pill px-3 shadow-sm">Gần đạt 🔥</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-end mb-2">
                                            <div class="small text-muted"> <strong class="text-primary"><?= $goal['current_fmt'] ?></strong> / <?= $goal['target_fmt'] ?> </div>
                                            <div class="small font-weight-bold text-primary"><?= $pct ?>%</div>
                                        </div>

                                        <div class="progress rounded-pill mb-3" style="height: 8px; background: #f1f5f9">
                                            <div class="goal-progress-fill <?= $isDone?'is-done':($isHot?'is-hot':'') ?>" 
                                                 style="width:<?= min(100,$pct) ?>%; background-color: <?= !$isDone&&!$isHot ? ($goal['color'] ?? '#2563eb') : '' ?>"></div>
                                        </div>

                                        <?php if (!$isDone): ?>
                                            <div class="bg-light p-2 rounded-lg mb-3 border small">
                                                <div class="d-flex justify-content-between">
                                                    <span>Thiếu: <strong><?= $goal['shortage_fmt'] ?></strong></span>
                                                    <?php if($goal['deadline_fmt']): ?>
                                                        <span>Hạn: <strong class="text-danger"><?= $goal['deadline_fmt'] ?></strong></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($goal['suggest'])): ?>
                                            <div class="text-primary small mb-3 font-weight-bold d-flex align-items-center gap-1">
                                                <i class="fas fa-magic"></i> <?= htmlspecialchars($goal['suggest']) ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="goal-actions d-flex gap-2" onclick="event.stopPropagation()">
                                            <?php if (!$isDone): ?>
                                            <button class="goal-btn text-success" onclick="GoalsApp.openTxModal(<?= $gId ?>,'add')"><i class="fas fa-plus"></i> Thu</button>
                                            <button class="goal-btn text-danger" onclick="GoalsApp.openTxModal(<?= $gId ?>,'subtract')"><i class="fas fa-minus"></i> Chi</button>
                                            <?php endif; ?>
                                            <button class="goal-btn" onclick="GoalsApp.openEditModal(<?= $gId ?>)"><i class="fas fa-pen"></i></button>
                                            <button class="goal-btn text-muted" onclick="GoalsApp.deleteGoal(<?= $gId ?>)"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- RIGHT — Detail panel -->
            <div class="col-xl-5">
                <div class="goal-detail-panel" id="goal-detail-panel">
                    <div class="p-5 text-center" id="goal-detail-empty">
                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:80px; height:80px">
                            <i class="fas fa-info-circle text-muted" style="font-size: 32px"></i>
                        </div>
                        <h6 class="font-weight-bold">Chi tiết mục tiêu</h6>
                        <p class="text-muted small">Chọn một mục tiêu bên trái để quản lý giao dịch và ghi chú.</p>
                    </div>

                    <div id="goal-detail-content" style="display:none">
                        <div class="goal-detail-header d-flex align-items-center">
                            <span id="dpanel-emoji" style="font-size: 1.5rem">🎯</span>
                            <div class="flex-grow-1 overflow-hidden">
                                <h6 class="mb-0 text-truncate font-weight-bold" id="dpanel-name">—</h6>
                                <p class="small mb-0 opacity-75" id="dpanel-current-line"></p>
                            </div>
                            <button onclick="GoalsApp.closeDetail()" class="btn btn-sm btn-link text-white p-0"><i class="fas fa-times"></i></button>
                        </div>

                        <div class="goal-tabs d-flex">
                            <button class="goal-tab-btn active" onclick="GoalsApp.switchTab('note',this)"><i class="fas fa-sticky-note mr-1"></i>Ghi chú</button>
                            <button class="goal-tab-btn" onclick="GoalsApp.switchTab('tx',this)"><i class="fas fa-exchange-alt mr-1"></i>Giao dịch</button>
                            <button class="goal-tab-btn" onclick="GoalsApp.switchTab('chart',this)"><i class="fas fa-chart-line mr-1"></i>Tiến độ</button>
                        </div>

                        <div class="p-5">
                            <!-- Tab: Note -->
                            <div id="tab-note" class="tab-pane-goal active">
                                <div id="dpanel-insights" class="mb-4"></div>
                                <label class="small font-weight-bold text-muted mb-2"><i class="fas fa-edit mr-1"></i>GHI CHÚ KẾ HOẠCH</label>
                                <textarea class="goal-note-area mb-3" id="dpanel-note" placeholder="Kế hoạch tiết kiệm cho mục tiêu này..."></textarea>
                                <button class="btn btn-goal-primary btn-block rounded-pill py-2 font-weight-bold" onclick="GoalsApp.saveNote()">
                                    <i class="fas fa-save mr-1"></i> LƯU GHI CHÚ
                                </button>
                            </div>

                            <!-- Tab: Transactions -->
                            <div id="tab-tx" class="tab-pane-goal d-none">
                                <div id="dpanel-tx-list" style="max-height: 480px; overflow-y: auto"></div>
                            </div>

                            <!-- Tab: Chart -->
                            <div id="tab-chart" class="tab-pane-goal d-none">
                                <div class="text-center mb-4 pt-3">
                                    <h1 class="font-weight-bold text-primary" id="dpanel-pct-big">0%</h1>
                                    <p class="text-muted small uppercase font-weight-bold">Tỷ lệ hoàn thành</p>
                                </div>
                                <canvas id="dpanel-chart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modals (Standardized) -->
<div class="modal fade" id="goalFormModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0" style="border-radius: 20px">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="font-weight-bold" id="goalFormTitle">Tạo mục tiêu</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="gf-id">
                <div class="form-group mb-3">
                    <label class="small font-weight-bold text-muted">Tên mục tiêu</label>
                    <input type="text" class="form-control rounded-pill px-3" id="gf-name" placeholder="VD: Mua nhà, Mua iPhone...">
                </div>
                <div class="row">
                    <div class="col-8">
                        <div class="form-group mb-3">
                            <label class="small font-weight-bold text-muted">Số tiền cần (đ)</label>
                            <input type="number" class="form-control rounded-pill px-3" id="gf-target" placeholder="25,000,000">
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group mb-3">
                            <label class="small font-weight-bold text-muted">Hạn (nếu có)</label>
                            <input type="date" class="form-control rounded-pill px-3" id="gf-deadline">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-4">
                        <div class="form-group">
                            <label class="small font-weight-bold text-muted">Emoji</label>
                            <input type="text" class="form-control rounded-pill text-center" id="gf-emoji" value="🎯">
                        </div>
                    </div>
                    <div class="col-8">
                        <div class="form-group">
                            <label class="small font-weight-bold text-muted">Tags (cách bằng dấu phẩy)</label>
                            <input type="text" class="form-control rounded-pill px-3" id="gf-tags" placeholder="nhà, xe, tech">
                        </div>
                    </div>
                </div>
                <div class="mt-2">
                    <label class="small font-weight-bold text-muted d-block">Màu sắc chủ đạo</label>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php foreach (['#000000ff','#2563eb','#059669','#d97706','#dc2626','#db2777','#1e293b'] as $color): ?>
                            <div class="rounded-circle border border-white shadow-sm color-pick-item" 
                                 data-color="<?= $color ?>" onclick="GoalsApp.pickColor('<?= $color ?>', this)"
                                 style="width:30px; height:30px; background:<?= $color ?>; cursor:pointer"></div>
                        <?php endforeach; ?>
                        <input type="color" id="gf-color" value="#2563eb" style="width:30px; height:30px; border:0; padding:0; background:transparent">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-goal-primary rounded-pill px-5 font-weight-bold" onclick="GoalsApp.submitGoalForm()">CẬP NHẬT</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Thêm tiền/Chi -->
<div class="modal fade" id="txModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow-lg border-0" style="border-radius: 20px">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="font-weight-bold" id="txModalTitle">Giao dịch</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="tx-goal-id">
                <input type="hidden" id="tx-id">
                <input type="hidden" id="tx-type" value="add">
                <div class="form-group mb-3">
                    <label class="small font-weight-bold text-muted">Số tiền (đ)</label>
                    <input type="number" class="form-control rounded-pill px-3" id="tx-amount" placeholder="500,000">
                </div>
                <div class="form-group">
                    <label class="small font-weight-bold text-muted">Ghi chú</label>
                    <input type="text" class="form-control rounded-pill px-3" id="tx-note" placeholder="Lương tháng, Thưởng...">
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-goal-primary btn-block rounded-pill py-2 font-weight-bold shadow" id="txSubmitBtn" onclick="GoalsApp.submitTx()">XÁC NHẬN</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';
    const BASE = '<?= url('') ?>/';
    const CSRF = '<?= $_SESSION['csrf_token'] ?? '' ?>';
    let _activeGoalId = null;
    let _chartInst    = null;

    async function apiRequest(path, method = 'GET', body = null) {
        const options = { method, headers: { 'X-Requested-With': 'XMLHttpRequest' } };
        if (body) {
            const fd = new FormData();
            Object.entries(body).forEach(([k, v]) => fd.append(k, v));
            fd.append('csrf_token', CSRF);
            options.body = fd;
        }
        const r = await fetch(BASE + path, options);
        return r.json();
    }

    function fmt(n) { return new Intl.NumberFormat('vi-VN').format(Number(n || 0)) + 'đ'; }

    window.GoalsApp = {
        filter(status) { window.location.href = BASE + 'admin/goals?status=' + status; },

        async selectGoal(id, cardEl) {
            console.log("Selecting goal:", id);
            _activeGoalId = id;
            if (cardEl) {
                $('.goal-item-card').removeClass('is-selected');
                $(cardEl).addClass('is-selected');
            } else {
                $('.goal-item-card').removeClass('is-selected');
                $(`#goal-card-${id}`).addClass('is-selected');
            }

            document.getElementById('goal-detail-empty').classList.add('d-none');
            document.getElementById('goal-detail-content').style.display = 'block';

            try {
                const data = await apiRequest('admin/goals/detail/' + id);
                if (!data.success) return;
                this._renderDetail(data.goal, data.transactions, data.tags);
            } catch (err) { console.error(err); }
        },

        _formatMoney(val) {
            return new Intl.NumberFormat('vi-VN').format(val) + 'đ';
        },

        _syncUI(data) {
            if (!data || !data.success) return;
            
            // 1. Update KPI
            if (data.stats) {
                $('#kpi-total').text(data.stats.total);
                $('#kpi-completed').text(data.stats.completed);
                $('#kpi-saved').text(this._formatMoney(data.stats.total_current));
                $('#kpi-target').text(this._formatMoney(data.stats.total_target));
            }

            // 2. Update Sidebar Card
            const g = data.goal;
            if (g) {
                const $card = $(`#goal-card-${g.id}`);
                if ($card.length) {
                    // Update amounts & percent
                    $card.find('.text-primary strong').first().text(g.current_fmt);
                    $card.find('.font-weight-bold.text-primary').text(g.percent + '%');
                    
                    // Update Progress Bar
                    const $bar = $card.find('.goal-progress-fill');
                    $bar.css('width', Math.min(100, g.percent) + '%');
                    
                    // Update Status Classes & Badges
                    $card.removeClass('border-success');
                    $bar.removeClass('is-done is-hot');
                    $card.find('.badge').remove();
                    
                    if (g.status === 'completed') {
                        $card.addClass('border-success');
                        $bar.addClass('is-done');
                        $card.find('.d-flex.gap-1').first().html('<span class="badge badge-success rounded-pill px-3 shadow-sm">Xong</span>');
                        $card.find('.goal-actions button').not(':last-child').hide();
                        $card.find('.goal-btn.text-success, .goal-btn.text-danger').hide();
                    } else {
                        if (g.is_hot) {
                            $bar.addClass('is-hot');
                            $card.find('.d-flex.gap-1').first().html('<span class="badge badge-danger rounded-pill px-3 shadow-sm">Gần đạt 🔥</span>');
                        }
                    }

                    // Update Shortage info
                    const shortageHtml = g.status !== 'completed' ? `
                        <div class="bg-light p-2 rounded-lg mb-3 border small">
                            <div class="d-flex justify-content-between">
                                <span>Thiếu: <strong>${g.shortage_fmt}</strong></span>
                                ${g.deadline_fmt ? `<span>Hạn: <strong class="text-danger">${g.deadline_fmt}</strong></span>` : ''}
                            </div>
                        </div>` : '';
                    $card.find('.bg-light.p-2.rounded-lg.mb-3').replaceWith(shortageHtml || '<div class="bg-light p-2 rounded-lg mb-3" style="display:none"></div>');

                    // Update Suggest
                    if (g.suggest) {
                        const suggestHtml = `<div class="text-primary small mb-3 font-weight-bold d-flex align-items-center gap-1"><i class="fas fa-magic"></i> ${g.suggest}</div>`;
                        const $oldSuggest = $card.find('.text-primary.small.mb-3');
                        if ($oldSuggest.length) $oldSuggest.replaceWith(suggestHtml);
                        else $card.find('.progress').after(suggestHtml);
                    } else {
                        $card.find('.text-primary.small.mb-3.font-weight-bold').remove();
                    }
                }
            }

            // 3. Refresh Detail Panel if this is the active goal
            if (g && _activeGoalId == g.id) {
                this.selectGoal(g.id);
            }
        },

        _renderDetail(g, txs, tags) {
            document.getElementById('dpanel-emoji').textContent = g.emoji || '🎯';
            document.getElementById('dpanel-name').textContent = g.name;
            document.getElementById('dpanel-current-line').textContent = fmt(g.current_amount) + ' / ' + fmt(g.target_amount);
            document.getElementById('dpanel-note').value = g.note || '';
            document.getElementById('dpanel-pct-big').textContent = (g.percent || 0) + '%';

            // Render AI Insights
            const insWrap = document.getElementById('dpanel-insights');
            const insights = g.insights || [];
            insWrap.innerHTML = insights.map(ins => `
                <div class="insight-box insight-box--${ins.level}">
                    <i class="${ins.icon}"></i>
                    <div>${escapeHtml(ins.text)}</div>
                </div>
            `).join('');

            const fill = document.getElementById('dpanel-chart');
            const txWrap = document.getElementById('dpanel-tx-list');
            
            if (!txs.length) {
                txWrap.innerHTML = '<div class="text-center p-4 text-muted small">Chưa có giao dịch</div>';
            } else {
                txWrap.innerHTML = txs.map(tx => `
                    <div class="tx-item">
                        <div class="d-flex align-items-center gap-3 min-width-0">
                            <div class="tx-icon ${tx.type==='add'?'tx-icon--add':'tx-icon--sub'}">
                                <i class="fas ${tx.type==='add'?'fa-plus':'fa-minus'}"></i>
                            </div>
                            <div class="min-width-0">
                                <div class="small font-weight-bold text-dark text-truncate">${escapeHtml(tx.note || (tx.type === 'add' ? 'Nạp vào' : 'Rút ra'))}</div>
                                <div class="text-muted" style="font-size:10px">${tx.created_fmt}</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3 ml-2">
                            <div class="font-weight-bold ${tx.type==='add'?'text-success':'text-danger'}" style="white-space:nowrap">
                                ${tx.type==='add'?'+':'-'}${tx.amount_fmt}
                            </div>
                            <div class="d-flex gap-1">
                                <span class="tx-action-btn edit" title="Sửa" onclick="GoalsApp.openEditTxModal(${tx.id}, ${tx.amount}, \`${escapeHtml(tx.note).replace(/`/g, '\\`').replace(/\$\{/g, '\\${')}\`)"><i class="fas fa-pen" style="font-size: 10px"></i></span>
                                <span class="tx-action-btn del" title="Xóa" onclick="GoalsApp.deleteTx(${tx.id})"><i class="fas fa-trash-alt" style="font-size: 10px"></i></span>
                            </div>
                        </div>
                    </div>
                `).join('');
            }

            this._renderChart(txs);
        },

        _renderChart(txs) {
            const canvas = document.getElementById('dpanel-chart');
            if (_chartInst) _chartInst.destroy();
            const days = txs.slice(0, 10).reverse();
            _chartInst = new Chart(canvas, {
                type: 'line',
                data: {
                    labels: days.map(t => t.created_fmt.split(' ')[1]),
                    datasets: [{
                        label: 'Lịch sử nạp/chi',
                        data: days.map(t => t.type==='add' ? t.amount : -t.amount),
                        borderColor: '#2563eb',
                        tension: 0.3,
                        fill: false
                    }]
                },
                options: { plugins: { legend: { display:false } }, scales: { y: { display: false } } }
            });
        },

        closeDetail() {
            _activeGoalId = null;
            document.querySelectorAll('.goal-item-card').forEach(c => c.classList.remove('is-active-selected'));
            document.getElementById('goal-detail-empty').classList.remove('d-none');
            document.getElementById('goal-detail-content').style.display = 'none';
        },

        switchTab(tab, btn) {
            document.querySelectorAll('.goal-tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-pane-goal').forEach(p => p.classList.add('d-none'));
            btn.classList.add('active');
            document.getElementById('tab-' + tab).classList.remove('d-none');
        },

        openCreateModal() {
            $('#goalFormTitle').text('Tạo mục tiêu mới');
            $('#gf-id').val(''); $('#gf-name').val(''); $('#gf-target').val(''); $('#gf-deadline').val(''); $('#gf-tags').val('');
            $('#goalFormModal').modal('show');
        },

        async openEditModal(id) {
            const data = await apiRequest('admin/goals/detail/' + id);
            if (!data.success) return;
            const g = data.goal;
            $('#goalFormTitle').text('Sửa mục tiêu');
            $('#gf-id').val(g.id); $('#gf-name').val(g.name); $('#gf-target').val(g.target_amount); $('#gf-deadline').val(g.deadline);
            $('#gf-emoji').val(g.emoji); $('#gf-tags').val((g.tags || []).join(',')); $('#gf-color').val(g.color || '#2563eb');
            $('#goalFormModal').modal('show');
        },

        pickColor(color, el) { 
            $('#gf-color').val(color); 
            $('.color-pick-item').removeClass('border-dark').addClass('border-white');
            if (el) $(el).removeClass('border-white').addClass('border-dark');
        },

        async submitGoalForm() {
            const id = $('#gf-id').val();
            const body = {
                name: $('#gf-name').val(), target_amount: $('#gf-target').val(), deadline: $('#gf-deadline').val(),
                emoji: $('#gf-emoji').val(), tags: $('#gf-tags').val(), color: $('#gf-color').val()
            };
            const data = await apiRequest(id ? 'admin/goals/update/' + id : 'admin/goals/create', 'POST', body);
            if (data.success) {
                $('#goalFormModal').modal('hide');
                SwalHelper.toast(data.message || 'Thành công');
                if (!id) location.reload(); // Reload for new goals to refresh sidebar list
                else this._syncUI(data);
            } else {
                SwalHelper.error(data.message);
            }
        },

        openTxModal(id, type) {
            $('#tx-goal-id').val(id); $('#tx-id').val(''); $('#tx-type').val(type); $('#tx-amount').val(''); $('#tx-note').val('');
            $('#txModalTitle').text(type === 'add' ? 'Thêm tiền tích lũy' : 'Ghi nhận chi tiêu');
            $('#txSubmitBtn').removeClass('btn-goal-primary btn-danger').addClass(type==='add'?'btn-goal-primary':'btn-danger');
            $('#txModal').modal('show');
        },

        openEditTxModal(txId, amount, note) {
            $('#tx-id').val(txId); $('#tx-amount').val(amount); $('#tx-note').val(note);
            $('#txModalTitle').text('Sửa giao dịch');
            $('#txSubmitBtn').removeClass('btn-danger').addClass('btn-goal-primary');
            $('#txModal').modal('show');
        },

        async deleteTx(txId) {
            SwalHelper.confirmDelete(async () => {
                KaiLoader.show();
                const data = await apiRequest('admin/goals/transaction/delete/' + txId, 'POST');
                KaiLoader.hide();
                if (data.success) {
                    this._syncUI(data);
                    SwalHelper.toast('Đã xóa giao dịch');
                } else {
                    SwalHelper.error(data.message);
                }
            });
        },

        async submitTx() {
            const goalId = $('#tx-goal-id').val();
            const txId   = $('#tx-id').val();
            const body   = { type: $('#tx-type').val(), amount: $('#tx-amount').val(), note: $('#tx-note').val() };
            
            let path = 'admin/goals/transaction/' + goalId;
            if (txId) path = 'admin/goals/transaction/update/' + txId;

            KaiLoader.show();
            const data = await apiRequest(path, 'POST', body);
            KaiLoader.hide();

            if (data.success) {
                $('#txModal').modal('hide');
                this._syncUI(data);
                SwalHelper.toast(data.message || 'Thành công');
            } else {
                SwalHelper.error(data.message);
            }
        },

        async deleteGoal(id) {
            SwalHelper.confirm('Xóa mục tiêu?', 'Tất cả giao dịch liên quan cũng sẽ bị xóa vĩnh viễn!', async () => {
                KaiLoader.show();
                const data = await apiRequest('admin/goals/delete/' + id, 'POST');
                KaiLoader.hide();
                if (data.success) location.reload(); // Reload on deletion to clean up list
            });
        },

        async saveNote() {
            if (!_activeGoalId) return;
            KaiLoader.show();
            const data = await apiRequest('admin/goals/note/' + _activeGoalId, 'POST', { note: $('#dpanel-note').val() });
            KaiLoader.hide();
            if (data.success) SwalHelper.toast('Đã lưu ghi chú'); else SwalHelper.error(data.message);
        }
    };

    function escapeHtml(s) {
        return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
</script>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>
