<?php
/**
 * Admin: Check Card PRO
 * Multi-tab gateway card checker — Integrated with Background Daemon.
 */
$pageTitle = 'Check Card';
$breadcrumbs = [
    ['label' => 'Công cụ', 'url' => url('admin')],
    ['label' => 'Check Card'],
];
require __DIR__ . '/../layout/head.php';
require __DIR__ . '/../layout/breadcrumb.php';

$gateways = $gateways ?? [];
$activeJobs = $activeJobs ?? [];
$historyLives = $historyLives ?? [];
?>

<link rel="stylesheet" href="<?= asset('assets/css/checkcard.css') ?>?v=<?= time() ?>">

<div class="admin-chatgpt-page">
    <section class="content pb-4 mt-1">
        <div class="container-fluid">

            <!-- ── GLOBAL SUMMARY CARDS ─────────────────────────────────────── -->
            <div class="row mb-3">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="gptb-stat-card gptb-stat-card--primary">
                        <div class="gptb-stat-icon"><i class="fas fa-layer-group"></i></div>
                        <div class="gptb-stat-body">
                            <div class="gptb-stat-label">Tổng xử lý</div>
                            <div class="gptb-stat-value" id="global-total">
                                <?= number_format($globalTotals['total'] ?? 0) ?></div>
                            <div class="gptb-stat-hint">Tổng số thẻ đã check</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="gptb-stat-card gptb-stat-card--success">
                        <div class="gptb-stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="gptb-stat-body">
                            <div class="gptb-stat-label">Approved</div>
                            <div class="gptb-stat-value" id="global-live">
                                <?= number_format($globalTotals['live'] ?? 0) ?></div>
                            <div class="gptb-stat-hint">Thẻ Live / Thành công</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="gptb-stat-card gptb-stat-card--danger">
                        <div class="gptb-stat-icon"><i class="fas fa-times-circle"></i></div>
                        <div class="gptb-stat-body">
                            <div class="gptb-stat-label">Declined</div>
                            <div class="gptb-stat-value" id="global-dead">
                                <?= number_format($globalTotals['dead'] ?? 0) ?></div>
                            <div class="gptb-stat-hint">Thẻ Die / Từ chối</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="gptb-stat-card gptb-stat-card--warning">
                        <div class="gptb-stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="gptb-stat-body">
                            <div class="gptb-stat-label">Error</div>
                            <div class="gptb-stat-value" id="global-err"><?= number_format($globalTotals['err'] ?? 0) ?>
                            </div>
                            <div class="gptb-stat-hint">Lỗi kết nối / Gateway</div>
                        </div>
                    </div>
                </div>
            </div>


        </div>

        <!-- ── TAB BAR ────────────────────────────────────────────────────── -->
        <div class="cc-tab-bar" id="tabBar">
            <?php foreach ($gateways as $gid => $gate): ?>
                <button class="cc-tab-btn <?= $gid == '1' ? 'active' : '' ?>" data-gate="<?= $gid ?>"
                    onclick="switchTab('<?= $gid ?>')">
                    <span class="cc-run-dot"></span>
                    <i class="<?= htmlspecialchars($gate['icon']) ?> mr-1"></i>
                    <?= htmlspecialchars($gate['name']) ?>
                    <span class="cc-badge ml-1" id="badge-live-<?= $gid ?>">0 live</span>
                </button>
            <?php endforeach; ?>
            <button class="cc-tab-btn" data-gate="bin_lookup" onclick="switchTab('bin_lookup')">
                <i class="fas fa-search mr-1"></i> Tra Cứu BIN
            </button>
        </div>

        <!-- ── GATE & BIN PANELS ──────────────────────────────────────────── -->
        <?php foreach ($gateways as $gid => $gate): ?>
            <div class="cc-tab-panel <?= $gid == '1' ? 'active' : '' ?>" id="panel-<?= $gid ?>">
                <div class="cc-gate-card">

                    <!-- Header -->
                    <div class="cc-gate-header">
                        <div class="cc-gate-title">
                            <h3 class="m-0 gptb-title-with-bar"><?= htmlspecialchars($gate['name']) ?></h3>
                        </div>
                        <div class="cc-gate-controls">
                            <button class="cc-btn cc-btn-clear" onclick="clearFeed('<?= $gid ?>')">
                                <i class="fas fa-trash-alt mr-1"></i> Xoá log
                            </button>
                            <button class="cc-btn cc-btn-copy" onclick="copyLives('<?= $gid ?>')">
                                <i class="fas fa-copy mr-1"></i> Copy LIVE
                            </button>
                            <button class="cc-btn cc-btn-export" onclick="exportLives('<?= $gid ?>')">
                                <i class="fas fa-download mr-1"></i> Export
                            </button>
                            <button class="cc-btn cc-btn-start" id="btn-start-<?= $gid ?>"
                                onclick="startChecker('<?= $gid ?>')">
                                <i class="fas fa-play mr-1"></i> Bắt đầu
                            </button>
                            <button class="cc-btn cc-btn-stop" id="btn-stop-<?= $gid ?>" style="display:none"
                                onclick="stopChecker('<?= $gid ?>')">
                                <i class="fas fa-stop mr-1"></i> Dừng
                            </button>
                        </div>
                    </div>

                    <!-- Stats Grid -->
                    <div class="cc-stats-modern">
                        <div class="cc-stat-m cc-stat-m--primary">
                            <div class="cc-stat-m-icon"><i class="fas fa-layer-group"></i></div>
                            <div class="cc-stat-m-body">
                                <div class="cc-stat-m-label">Tổng xử lý</div>
                                <div class="cc-stat-m-value" id="s-total-<?= $gid ?>">0</div>
                            </div>
                        </div>
                        <div class="cc-stat-m cc-stat-m--success">
                            <div class="cc-stat-m-icon"><i class="fas fa-check-circle"></i></div>
                            <div class="cc-stat-m-body">
                                <div class="cc-stat-m-label">Approved</div>
                                <div class="cc-stat-m-value" id="s-live-<?= $gid ?>">0</div>
                            </div>
                        </div>
                        <div class="cc-stat-m cc-stat-m--danger">
                            <div class="cc-stat-m-icon"><i class="fas fa-times-circle"></i></div>
                            <div class="cc-stat-m-body">
                                <div class="cc-stat-m-label">Declined</div>
                                <div class="cc-stat-m-value" id="s-dead-<?= $gid ?>">0</div>
                            </div>
                        </div>
                        <div class="cc-stat-m cc-stat-m--warning">
                            <div class="cc-stat-m-icon"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="cc-stat-m-body">
                                <div class="cc-stat-m-label">Error</div>
                                <div class="cc-stat-m-value" id="s-err-<?= $gid ?>">0</div>
                            </div>
                        </div>
                    </div>

                    <!-- Progress -->
                    <div class="cc-progress-wrap">
                        <div class="cc-progress-bar">
                            <div class="cc-progress-fill" id="prog-<?= $gid ?>"></div>
                        </div>
                        <div
                            style="display:flex; justify-content:space-between; margin-top:8px; font-size:12px; color:var(--cc-muted)">
                            <span id="prog-txt-<?= $gid ?>"></span>
                            <span id="prog-pct-<?= $gid ?>"></span>
                        </div>
                    </div>

                    <div class="cc-body" style="display: flex; gap: 20px; align-items: stretch; flex-wrap: wrap;">
                        <!-- Column 1: Live Results (Left) -->
                        <div class="cc-col cc-live-col"
                            style="flex: 1; min-width: 300px; display: flex; flex-direction: column;">
                            <div class="cc-col-header cc-live-header" style="background:#f8f9fa; padding:12px 15px; border-bottom:1px solid #e9ecef;">
                                <span style="display: flex; align-items: center; gap: 8px;"><i class="fas fa-list-ul text-muted"></i> LIVE CARD</span>
                                <span id="live-count-label-<?= $gid ?>" class="badge badge-success cc-badge-norm approved" style="padding: 4px 10px; font-size: 11px;">0 THẺ</span>
                            </div>
                            <div class="cc-live-list" id="lives-<?= $gid ?>"
                                style="flex: 1; max-height: 400px; overflow-y: auto;">
                                <?php if (!empty($historyLives[$gid])): ?>
                                    <?php foreach ($historyLives[$gid] as $row): ?>
                                        <div class="cc-live-entry">
                                            <div class="cc-result-card-row">
                                                <span class="cc-badge-norm approved">Approved ✅</span>
                                                <span style="color:var(--cc-muted); font-size:11px">[<?= explode(' ', $row['created_at'])[1] ?? '' ?>]</span>
                                            </div>
                                            <div class="cc-result-card-row" style="font-size:15px; margin: 8px 0">
                                                <?= htmlspecialchars($row['card']) ?>
                                            </div>
                                            <div style="display:flex; flex-wrap:wrap; gap:4px">
                                                <span class="cc-meta-chip country">🌍 <?= htmlspecialchars($row['country'] ?? 'Unknown') ?> <?= $row['flag'] ?? '🏳' ?></span>
                                                <span class="cc-meta-chip bank">🏛 <?= htmlspecialchars($row['bank'] ?? 'Unknown') ?></span>
                                                <span class="cc-meta-chip scheme">💳 <?= htmlspecialchars($row['scheme'] ?? '?') ?></span>
                                                <span class="cc-meta-chip type"><?= htmlspecialchars($row['type'] ?? '?') ?> / <?= htmlspecialchars($row['brand'] ?? 'CLASSIC') ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div style="color:var(--cc-muted);font-size:12px;text-align:center;padding:15px 0">Chưa có thẻ LIVE nào.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Column 2: Settings (Right) -->
                        <div class="cc-col cc-settings-col"
                            style="flex: 1; min-width: 300px; display: flex; flex-direction: column; background:#fff; border:1px solid var(--cc-border); border-radius:8px; overflow:hidden;">
                            <div class="cc-col-header"
                                style="background:#f8f9fa; color:#495057; border-bottom:1px solid #e9ecef; font-weight:700; padding:12px 15px; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-cog text-muted"></i> CẤU HÌNH GATEWAY
                            </div>
                             <div style="padding: 15px; flex: 1; display:flex; flex-direction:column; gap:8px;">
                                <div class="cc-field">
                                    <label>API URL (End Point)</label>
                                    <input type="text" id="g-api-url-<?= $gid ?>" value="<?= htmlspecialchars(
                                          isset($gate['api_url']) ? $gate['api_url'] : 'http://178.128.110.246' . ($gate['path'] ?? '')
                                      ) ?>">
                                </div>
                                <div class="cc-field">
                                    <label>API Request Param</label>
                                    <input type="text" id="g-api-param-<?= $gid ?>"
                                        value="<?= htmlspecialchars($gate['param']) ?>">
                                </div>
                                <div class="cc-field">
                                    <label>BIN / Prefix</label>
                                    <input type="text" id="g-bin-<?= $gid ?>" value="515462" placeholder="6–8 số">
                                </div>
                                <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:6px;">
                                    <div class="cc-field">
                                        <label>MM</label>
                                        <input type="text" id="g-mm-<?= $gid ?>" placeholder="RN" style="text-align: center; padding: 8px 4px;">
                                    </div>
                                    <div class="cc-field">
                                        <label>YY</label>
                                        <input type="text" id="g-yy-<?= $gid ?>" placeholder="RN" style="text-align: center; padding: 8px 4px;">
                                    </div>
                                    <div class="cc-field">
                                        <label>CVV</label>
                                        <input type="text" id="g-cvv-<?= $gid ?>" placeholder="RN" style="text-align: center; padding: 8px 4px;">
                                    </div>
                                </div>
                                <div style="display:flex; gap:10px;">
                                </div>
                                <div class="cc-field">
                                    <label>Thread / Gate</label>
                                    <div class="cc-slider-wrap">
                                        <input type="range" id="g-threads-<?= $gid ?>" min="1" max="50" value="20"
                                            oninput="document.getElementById('g-tval-<?= $gid ?>').textContent=this.value">
                                        <span id="g-tval-<?= $gid ?>"
                                            style="color:var(--cc-text);font-weight:700;font-size:15px">20</span>
                                    </div>
                                </div>
                                <div style="margin-top:auto; padding-top:15px;">
                                    <button class="cc-btn cc-btn-save w-100"
                                        style="width: 100%; height: 42px; font-size: 14px;"
                                        onclick="saveSettings('<?= $gid ?>')">
                                        <i class="fas fa-save mr-1"></i> LƯU CẤU HÌNH NÀY
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div><!-- /cc-gate-card -->
            </div><!-- /cc-tab-panel -->
        <?php endforeach; ?>

        <!-- ── BIN LOOKUP PANEL ───────────────────────────────────────────── -->
        <div class="cc-tab-panel" id="panel-bin_lookup">
            <div class="cc-gate-card"
                style="display:flex; flex-wrap:wrap; gap:20px; background:transparent; padding:0; box-shadow:none;">
                <!-- Search Box -->
                <div
                    style="flex:1; min-width:300px; background:#fff; border-radius:10px; border:1px solid var(--cc-border); box-shadow:var(--cc-shadow); padding:20px;">
                    <h3 style="font-size:18px; font-weight:700; margin-bottom:15px; color:var(--cc-text);"><i
                            class="fas fa-search text-primary mr-2"></i> TRA CỨU BIN NHANH</h3>
                    <p style="font-size:14px; color:var(--cc-muted); margin-bottom:20px;">
                        Có thể dán thẳng toàn bộ thẻ (VD: <code>374355126445010|09|31|2403</code>) hoặc nhập 6-8 số đầu.
                    </p>
                    <div class="cc-field mb-3">
                        <input type="text" id="bin-input" placeholder="Dán thẻ / Nhập BIN..."
                            style="font-size: 18px; padding:15px; text-align:center; font-family:monospace; font-weight:bold;">
                    </div>
                    <button id="btn-lookup" class="cc-btn btn-primary w-100"
                        style="background:var(--cc-blue); color:#fff; border:none; padding:12px; font-size:16px; border-radius:8px;"
                        onclick="performBinLookup()">
                        <i class="fas fa-search"></i> TRA CỨU NGAY
                    </button>
                </div>

                <!-- Result Box -->
                <div
                    style="flex:2; min-width:400px; background:#fff; border-radius:10px; border:1px solid var(--cc-border); box-shadow:var(--cc-shadow); padding:20px; display:flex; flex-direction:column; justify-content:center; align-items:center;">
                    <!-- Initial -->
                    <div id="res-empty" style="text-align:center; color:var(--cc-muted);">
                        <i class="fas fa-credit-card mb-3" style="font-size:48px; opacity:0.2;"></i>
                        <h5>Chưa có dữ liệu</h5>
                        <p style="font-size:14px;">Vui lòng nhập BIN và bấm Tra Cứu.</p>
                    </div>

                    <!-- Loading -->
                    <div id="res-loading" style="display:none; text-align:center; color:var(--cc-blue);">
                        <i class="fas fa-spinner fa-spin mb-3" style="font-size:40px;"></i>
                        <h5 style="color:var(--cc-text);">Đang tra cứu dữ liệu...</h5>
                    </div>

                    <!-- Error -->
                    <div id="res-error" style="display:none; text-align:center; color:var(--cc-red);">
                        <i class="fas fa-exclamation-triangle mb-3" style="font-size:48px; opacity:0.8;"></i>
                        <h5>Lỗi Tra Cứu</h5>
                        <p id="error-msg" style="font-size:14px; max-width:400px; margin:0 auto;"></p>
                    </div>

                    <!-- Data -->
                    <div id="res-data" style="display:none; width:100%;">
                        <div
                            style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px dashed #eee; padding-bottom:15px; margin-bottom:25px;">
                            <div style="font-size:28px; font-weight:900; letter-spacing:2px; font-family:monospace; color:var(--cc-text);"
                                id="d-bin">515462</div>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <button class="btn btn-sm btn-outline-primary" style="padding: 6px 12px; font-size: 13px; font-weight: 600; border-radius: 6px;" onclick="copyBinInfo()"><i class="fas fa-copy mr-1"></i> COPY</button>
                                <div id="d-brand-badge" class="badge badge-primary px-3 py-2"
                                    style="font-size:14px; text-transform:uppercase;">MASTERCARD</div>
                            </div>
                        </div>

                        <div class="row w-100 m-0">
                            <div class="col-sm-6 mb-4 px-1">
                                <div
                                    style="font-size:12px; color:var(--cc-muted); text-transform:uppercase; font-weight:700; margin-bottom:5px;">
                                    Ngân hàng cấp thẻ</div>
                                <div
                                    style="font-size:18px; font-weight:600; color:var(--cc-text); display:flex; align-items:center;">
                                    <i class="fas fa-university text-primary mr-2"></i> <span id="d-bank">Ví dụ
                                        Bank</span>
                                </div>
                            </div>
                            <div class="col-sm-6 mb-4 px-1">
                                <div
                                    style="font-size:12px; color:var(--cc-muted); text-transform:uppercase; font-weight:700; margin-bottom:5px;">
                                    Quốc gia</div>
                                <div
                                    style="font-size:18px; font-weight:600; color:var(--cc-text); display:flex; align-items:center;">
                                    <span id="d-flag" class="mr-2" style="font-size:24px;">🇻🇳</span> <span
                                        id="d-country">Vietnam</span>
                                </div>
                            </div>
                            <div class="col-sm-6 mb-4 px-1">
                                <div
                                    style="font-size:12px; color:var(--cc-muted); text-transform:uppercase; font-weight:700; margin-bottom:5px;">
                                    Loại thẻ</div>
                                <div
                                    style="font-size:18px; font-weight:600; color:var(--cc-text); display:flex; align-items:center;">
                                    <i class="fas fa-credit-card text-success mr-2"></i> <span id="d-type"
                                        style="text-transform:capitalize;">Credit</span>
                                </div>
                            </div>
                            <div class="col-sm-6 mb-4 px-1">
                                <div
                                    style="font-size:12px; color:var(--cc-muted); text-transform:uppercase; font-weight:700; margin-bottom:5px;">
                                    Hạng thẻ</div>
                                <div
                                    style="font-size:18px; font-weight:600; color:var(--cc-text); display:flex; align-items:center;">
                                    <i class="fas fa-star text-warning mr-2"></i> <span id="d-level"
                                        style="text-transform:uppercase;">CLASSIC</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

</div>
</section>
</div>

<script>
    /* ═══════════════════════════════════════════════════════════════════
       CHECK CARD PRO — Background Daemon Polling Engine
    ═══════════════════════════════════════════════════════════════════ */

    const API_START = '<?= url('admin/check-card/start-job') ?>';
    const API_STOP = '<?= url('admin/check-card/stop-job') ?>';
    const API_STATUS = '<?= url('admin/check-card/status') ?>';
    const API_CLEAR = '<?= url('admin/check-card/clear-log') ?>';
    const MAX_FEED_ITEMS = 10000;
    const MAX_LIVE_ITEMS = 10000;

    const GATES = {};
    <?php foreach ($gateways as $gid => $gate): ?>
        <?php 
           $lastId = 0;
           $storedCards = [];
           if (!empty($historyLives[$gid])) {
               $lastId = end($historyLives[$gid])['id'];
               foreach($historyLives[$gid] as $rl) $storedCards[] = $rl['card'];
           }
        ?>
        GATES['<?= $gid ?>'] = {
            gateId: '<?= $gid ?>',
            name: '<?= addslashes($gate['name']) ?>',
            jobId: 0,
            running: false,
            lastLive: <?= $lastId ?>,
            lives: <?= json_encode($storedCards) ?>,
            prevChecked: 0,
            lastUpdate: 0,
        };
    <?php endforeach; ?>

    // === SETTINGS PERSISTENCE ===
    const STORAGE_KEY = 'kaishop_cc_settings';

    function saveSettings(gid) {
        const cfg = getSettings(gid);
        let globalCfg = {};
        try {
            globalCfg = JSON.parse(localStorage.getItem(STORAGE_KEY)) || {};
        } catch (e) { }

        globalCfg[gid] = cfg;
        localStorage.setItem(STORAGE_KEY, JSON.stringify(globalCfg));
        showToast(gid ? 'Đã lưu cấu hình' : 'Đã lưu cấu hình');
    }

    function loadSettings() {
        try {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved) {
                const globalCfg = JSON.parse(saved);
                Object.keys(GATES).forEach(gid => {
                    const data = globalCfg[gid] || globalCfg; // fallback to global if migrating from old format
                    if (data && typeof data === 'object') {
                        if (data.api_url) document.getElementById(`g-api-url-${gid}`).value = data.api_url;
                        if (data.api_param) document.getElementById(`g-api-param-${gid}`).value = data.api_param;
                        if (data.bin) document.getElementById(`g-bin-${gid}`).value = data.bin;
                        if (data.mm) document.getElementById(`g-mm-${gid}`).value = data.mm === 'RN' ? '' : data.mm;
                        if (data.yy) document.getElementById(`g-yy-${gid}`).value = data.yy === 'RN' ? '' : data.yy;
                        if (data.cvv) document.getElementById(`g-cvv-${gid}`).value = data.cvv === 'RN' ? '' : data.cvv;
                        if (data.threads) {
                            document.getElementById(`g-threads-${gid}`).value = data.threads;
                            document.getElementById(`g-tval-${gid}`).textContent = data.threads;
                        }
                    }
                });
            }
        } catch (e) { console.error("Load settings fail:", e); }
    }

    document.addEventListener('DOMContentLoaded', async () => {
        loadSettings();

        // RESTORE ACTIVE TAB
        const savedTab = localStorage.getItem('active_gate_tab');
        if (savedTab && GATES[savedTab]) {
            switchTab(savedTab);
        }

        // Auto-resume existing active jobs from server render
        <?php if (!empty($activeJobs)): ?>
            <?php foreach ($activeJobs as $job): ?>
                if (GATES['<?= $job['gate_id'] ?>']) {
                    GATES['<?= $job['gate_id'] ?>'].jobId = <?= $job['id'] ?>;
                    GATES['<?= $job['gate_id'] ?>'].running = <?= $job['status'] === 'running' ? 'true' : 'false' ?>;
                    if (GATES['<?= $job['gate_id'] ?>'].running) {
                        document.querySelector(`[data-gate="<?= $job['gate_id'] ?>"]`).classList.add('running');
                        document.getElementById(`btn-start-<?= $job['gate_id'] ?>`).style.display = 'none';
                        document.getElementById(`btn-stop-<?= $job['gate_id'] ?>`).style.display = 'inline-flex';
                    }
                }
            <?php endforeach; ?>
        <?php endif; ?>

        // Bootstrap: Fetch status immediately to populate logs
        setTimeout(pollStatus, 500);
    });

    function startAll() {
        Object.keys(GATES).forEach(gid => {
            if (!GATES[gid].running) startChecker(gid);
        });
    }
    function stopAll() {
        Object.keys(GATES).forEach(gid => {
            if (GATES[gid].running) stopChecker(gid);
        });
    }

    function getSettings(gid) {
        return {
            api_url: document.getElementById(`g-api-url-${gid}`).value.trim(),
            api_param: document.getElementById(`g-api-param-${gid}`).value.trim() || 'card',
            bin: document.getElementById(`g-bin-${gid}`).value.trim() || '515462',
            mm: document.getElementById(`g-mm-${gid}`).value.trim().toUpperCase() || 'RN',
            yy: document.getElementById(`g-yy-${gid}`).value.trim().toUpperCase() || 'RN',
            cvv: document.getElementById(`g-cvv-${gid}`).value.trim().toUpperCase() || 'RN',
            batch: 0,
            threads: parseInt(document.getElementById(`g-threads-${gid}`).value) || 20,
        };
    }

    function switchTab(gid) {
        document.querySelectorAll('.cc-tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.cc-tab-panel').forEach(p => p.classList.remove('active'));

        document.querySelector(`[data-gate="${gid}"]`).classList.add('active');
        document.getElementById(`panel-${gid}`).classList.add('active');

        // Save state
        localStorage.setItem('active_gate_tab', gid);
    }

    async function startChecker(gid) {
        const g = GATES[gid];
        if (g.running) return;

        const cfg = getSettings(gid);
        cfg.gate_id = gid;

        const startBtn = document.getElementById(`btn-start-${gid}`);
        startBtn.disabled = true;
        startBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';

        try {
            const res = await fetch(API_START, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(cfg)
            });
            const data = await res.json();

            if (data.error) {
                Swal.fire('Lỗi API', data.error, 'error');
                startBtn.disabled = false;
                startBtn.innerHTML = '<i class="fas fa-play"></i> Bắt đầu';
                return;
            }

            g.jobId = data.job_id;
            g.running = true;
            // Removed: g.lastLive = 0; (Stay with current history)

            startBtn.style.display = 'none';
            startBtn.disabled = false;
            startBtn.innerHTML = '<i class="fas fa-play"></i> Bắt đầu';
            document.getElementById(`btn-stop-${gid}`).style.display = 'inline-flex';
            document.querySelector(`[data-gate="${gid}"]`).classList.add('running');

            setProgText(gid, '');

        } catch (e) {
            Swal.fire('Lỗi kết nối', 'Không thể kết nối đến Server Daemon: ' + e, 'error');
            startBtn.disabled = false;
            startBtn.innerHTML = '<i class="fas fa-play"></i> Bắt đầu';
        }
    }

    async function stopChecker(gid) {
        const g = GATES[gid];
        if (!g.jobId) return;

        try {
            await fetch(API_STOP, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ job_id: g.jobId })
            });
        } catch (e) { }

        g.running = false;
        document.getElementById(`btn-start-${gid}`).style.display = 'inline-flex';
        document.getElementById(`btn-stop-${gid}`).style.display = 'none';
        document.querySelector(`[data-gate="${gid}"]`).classList.remove('running');
        setProgText(gid, '');
    }

    async function clearFeed(gid) {
        const g = GATES[gid];
        if (g.jobId) {
            try {
                await fetch(API_CLEAR, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ job_id: g.jobId })
                });
            } catch (e) { }
        }
        document.getElementById(`lives-${gid}`).innerHTML = `<div style="color:var(--cc-muted);font-size:12px;text-align:center;padding:15px 0">Dữ liệu log đã được dọn sạch.</div>`;
        // Keep stats
        g.lastLive = 0;
        g.lives = [];
        g.jobId = 0;
        document.getElementById(`prog-${gid}`).style.width = '0%';
        document.getElementById(`prog-pct-${gid}`).textContent = '0%';
        setProgText(gid, '');
    }

    // === CENTRAL POLLING LOOP ===
    async function pollStatus() {
        const activeJobIds = [];
        Object.values(GATES).forEach(g => {
            if (g.jobId && g.running) {
                activeJobIds.push(g.jobId);
            }
        });

        // Force load stats even if no jobs are running
        // if (activeJobIds.length === 0) return;

        try {
            const queryParams = [`job_ids=${activeJobIds.join(',')}`];
            Object.values(GATES).forEach(g => {
                if (g.jobId) queryParams.push(`last_live_${g.jobId}=${g.lastLive}`);
            });

            const res = await fetch(API_STATUS + '?' + queryParams.join('&'));
            const data = await res.json();

            if (!data.jobs) return;

            let gTotal = 0, gLive = 0, gDead = 0, gErr = 0;
            data.jobs.forEach(job => {
                const gid = job.gate_id;
                const g = GATES[gid];
                if (!g) return;

                // Global sums
                gTotal += parseInt(job.checked_count || 0);
                gLive += parseInt(job.live_count || 0);
                gDead += parseInt(job.dead_count || 0);
                gErr += parseInt(job.err_count || 0);

                document.getElementById(`s-total-${gid}`).textContent = job.checked_count;
                document.getElementById(`s-live-${gid}`).textContent = job.live_count;
                document.getElementById(`s-dead-${gid}`).textContent = job.dead_count;
                document.getElementById(`s-err-${gid}`).textContent = job.err_count;
                document.getElementById(`badge-live-${gid}`).textContent = `${job.live_count || 0} live`;
                document.getElementById(`live-count-label-${gid}`).textContent = `${job.live_count || 0} thẻ`;

                const now = Date.now();
                g.prevChecked = job.checked_count;
                g.lastUpdate = now;

                const progFill = document.getElementById(`prog-${gid}`);
                const progPct = document.getElementById(`prog-pct-${gid}`);

                if (job.status === 'running') {
                    progFill.classList.add('pulsing');
                    progPct.textContent = 'RUNNING';
                    setProgText(gid, '');
                } else {
                    progFill.classList.remove('pulsing');
                    progFill.style.width = '0%';
                    progPct.textContent = '';
                    setProgText(gid, '');
                }

                const jobLives = data.lives[job.id];
                if (jobLives && jobLives.length > 0) {
                    const liveEl = document.getElementById(`lives-${gid}`);
                    if (g.lives.length === 0) liveEl.innerHTML = '';

                    jobLives.forEach(row => {
                        if (row.id > g.lastLive) g.lastLive = parseInt(row.id);
                        g.lives.push(row.card);

                        const div = document.createElement('div');
                        div.className = 'cc-live-entry';

                        // Standard Premium Chip Design
                        div.innerHTML = `
                        <div class="cc-result-card-row">
                            <span class="cc-badge-norm approved">Approved ✅</span>
                            <span style="color:var(--cc-muted); font-size:11px">[${(row.created_at || '').split(' ')[1] || ''}]</span>
                        </div>
                        <div class="cc-result-card-row" style="font-size:15px; margin: 8px 0">
                            ${esc(row.card)}
                        </div>
                        <div style="display:flex; flex-wrap:wrap; gap:4px">
                            <span class="cc-meta-chip country">🌍 ${esc(row.country || 'Unknown')} ${row.flag || '🏳'}</span>
                            <span class="cc-meta-chip bank">🏛 ${esc(row.bank || 'Unknown')}</span>
                            <span class="cc-meta-chip scheme">💳 ${esc(row.scheme || '?')}</span>
                            <span class="cc-meta-chip type">${esc(row.type || '?')} / ${esc(row.brand || 'CLASSIC')}</span>
                        </div>
                    `;
                        const shouldStickToBottom = isNearBottom(liveEl);
                        liveEl.appendChild(div);
                        trimContainerItems(liveEl, MAX_LIVE_ITEMS);
                    });
                }

                if (job.status !== 'running') {
                    g.running = false;
                    document.getElementById(`btn-start-${gid}`).style.display = 'inline-flex';
                    document.getElementById(`btn-stop-${gid}`).style.display = 'none';
                    document.querySelector(`[data-gate="${gid}"]`).classList.remove('running');
                }
            });

            // Update Global Stats from DB
            if (data.global_totals) {
                document.getElementById('global-total').textContent = data.global_totals.total;
                document.getElementById('global-live').textContent = data.global_totals.live;
                document.getElementById('global-dead').textContent = data.global_totals.dead;
                document.getElementById('global-err').textContent = data.global_totals.err;
            }

        } catch (e) { console.error("Poll fail:", e); }
    }

    setInterval(pollStatus, 3000);

    function trimContainerItems(container, maxItems) {
        while (container.children.length > maxItems) {
            container.removeChild(container.firstElementChild);
        }
    }

    function isNearBottom(container) {
        const threshold = 48;
        return container.scrollHeight - container.scrollTop - container.clientHeight <= threshold;
    }

    function scrollContainerToBottom(container) {
        container.scrollTo({
            top: container.scrollHeight,
            behavior: 'smooth'
        });
    }

    // Auto scroll feed when there's an update (Sticky bottom scroll)
    function setupAutoScroll() {
        // Track all live list containers
        document.querySelectorAll('.cc-live-list').forEach(el => {
            // Remember user's intent to scroll up instead of auto-scroll
            el.addEventListener('scroll', () => {
                el._isUserScrolledUp = el.scrollHeight - el.scrollTop - el.clientHeight > 50;
            });

            // Setup observer to watch for children being added
            const observer = new MutationObserver(() => {
                if (!el._isUserScrolledUp) {
                    scrollContainerToBottom(el);
                }
            });
            observer.observe(el, { childList: true });
        });
    }

    // Setup auto scroll when DOM loads
    document.addEventListener('DOMContentLoaded', () => {
        setupAutoScroll();
    });

    function copyLives(gid) {
        const lives = GATES[gid].lives;
        if (!lives.length) return Swal.fire('Thông báo', 'Chưa có thẻ LIVE nào để copy!', 'info');
        copyText(lives.join('\n'));
        showToast(`✅ Đã copy ${lives.length} thẻ`);
    }

    function exportLives(gid) {
        const g = GATES[gid];
        if (!g.lives.length) return Swal.fire('Thông báo', 'Chưa có thẻ LIVE nào để xuất!', 'info');
        const blob = new Blob([g.lives.join('\n')], { type: 'text/plain' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = `lives_gate${gid}_${Date.now()}.txt`;
        a.click();
    }

    function setProgText(gid, txt) { document.getElementById(`prog-txt-${gid}`).textContent = txt; }
    function copyText(t) { navigator.clipboard ? navigator.clipboard.writeText(t) : legacyCopy(t); }
    function legacyCopy(t) {
        const ta = document.createElement('textarea'); ta.value = t; ta.style.position = 'fixed';
        document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
    }
    function showToast(m) {
        Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        }).fire({
            icon: 'success',
            title: m
        });
    }
    function esc(s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }

    // === BIN LOOKUP LOGIC === //
    document.getElementById('bin-input').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') performBinLookup();
    });

    async function performBinLookup() {
        // Extract first continuous block of 6-8 digits from pasted text (handles cards like 374355126445010|...)
        const rawText = document.getElementById('bin-input').value;
        const matches = rawText.match(/\d{6,8}/);

        if (!matches || matches.length === 0) {
            Swal.fire({ icon: 'warning', title: 'BIN không hợp lệ', text: 'Vui lòng dán thẻ hoặc nhập ít nhất 6 số đầu.' });
            return;
        }

        let bin = matches[0].substring(0, 8); // fallback maximum 8 digits

        showBinState('loading');
        document.getElementById('btn-lookup').disabled = true;

        try {
            const response = await fetch(`<?= url('admin/api/bin-lookup') ?>?bin=${bin}`);
            document.getElementById('btn-lookup').disabled = false;

            if (response.status === 404) return showBinState('error', 'Không tìm thấy dữ liệu cho BIN (' + bin + ').');
            if (response.status === 429) return showBinState('error', 'Trang tra cứu đang quá tải (Rate Limit). Thử lại sau.');
            if (!response.ok) return showBinState('error', 'Máy chủ tra cứu lỗi: ' + response.status);

            const data = await response.json();

            document.getElementById('d-bin').textContent = bin;
            const badge = document.getElementById('d-brand-badge');
            badge.textContent = data.scheme || 'N/A';

            const schemeLow = (data.scheme || '').toLowerCase();
            badge.className = 'badge px-3 py-2 text-white';
            if (schemeLow === 'visa') badge.style.backgroundColor = '#1a1f71';
            else if (schemeLow === 'mastercard') badge.style.backgroundColor = '#eb001b';
            else if (schemeLow === 'amex') badge.style.backgroundColor = '#002663';
            else if (schemeLow === 'discover') badge.style.backgroundColor = '#f9a021';
            else badge.classList.add('badge-secondary');

            document.getElementById('d-bank').textContent = data.bank?.name || 'Unknown Bank';
            document.getElementById('d-country').textContent = data.country?.name || 'Unknown';
            document.getElementById('d-flag').textContent = data.country?.emoji || '🌍';
            document.getElementById('d-type').textContent = data.type || 'N/A';
            document.getElementById('d-level').textContent = data.brand || 'N/A';

            showBinState('data');
        } catch (err) {
            document.getElementById('btn-lookup').disabled = false;
            showBinState('error', 'Lỗi mạng: ' + err.message);
        }
    }

    function copyBinInfo() {
        const bin = document.getElementById('d-bin').textContent;
        const brand = document.getElementById('d-brand-badge').textContent;
        const bank = document.getElementById('d-bank').textContent;
        const country = document.getElementById('d-country').textContent;
        const flag = document.getElementById('d-flag').textContent;
        const type = document.getElementById('d-type').textContent;
        const level = document.getElementById('d-level').textContent;

        const text = `Bin : ${bin}\nCountry : ${country} ${flag}\nBrand : ${brand}\nLevel : ${level}\nType : ${type}\nBank : ${bank}`;
        copyText(text);
        showToast('Đã copy thông tin BIN');
    }

    function showBinState(state, msg = '') {
        ['res-empty', 'res-loading', 'res-error', 'res-data'].forEach(id => document.getElementById(id).style.display = 'none');
        if (state === 'error') {
            document.getElementById('res-error').style.display = 'block';
            document.getElementById('error-msg').textContent = msg;
        } else {
            document.getElementById(`res-${state}`).style.display = 'block';
        }
    }
</script>

<?php require __DIR__ . '/../layout/foot.php'; ?>