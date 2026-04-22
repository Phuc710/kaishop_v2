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
                            <div class="gptb-stat-value" id="global-total">0</div>
                            <div class="gptb-stat-hint">Tổng số thẻ đã check</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="gptb-stat-card gptb-stat-card--success">
                        <div class="gptb-stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="gptb-stat-body">
                            <div class="gptb-stat-label">Approved</div>
                            <div class="gptb-stat-value" id="global-live">0</div>
                            <div class="gptb-stat-hint">Thẻ Live / Thành công</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="gptb-stat-card gptb-stat-card--danger">
                        <div class="gptb-stat-icon"><i class="fas fa-times-circle"></i></div>
                        <div class="gptb-stat-body">
                            <div class="gptb-stat-label">Declined</div>
                            <div class="gptb-stat-value" id="global-dead">0</div>
                            <div class="gptb-stat-hint">Thẻ Die / Từ chối</div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="gptb-stat-card gptb-stat-card--warning">
                        <div class="gptb-stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="gptb-stat-body">
                            <div class="gptb-stat-label">Error</div>
                            <div class="gptb-stat-value" id="global-err">0</div>
                            <div class="gptb-stat-hint">Lỗi kết nối / Gateway</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="cc-settings-card">
                <div class="cc-gate-header" style="padding: 0 0 15px 0; margin-bottom: 20px; background: transparent;">
                    <div class="cc-gate-title">
                        <h2 class="gptb-title-with-bar">Cấu hình chung</h2>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <button class="cc-btn cc-btn-stop" onclick="stopAll()">
                            <i class="fas fa-stop"></i> DỪNG HẾT
                        </button>
                        <button class="cc-btn cc-btn-start" onclick="startAll()">
                            <i class="fas fa-play"></i> CHẠY HẾT
                        </button>
                        <button class="cc-btn btn-success" style="background: var(--cc-green); color:#fff; border:none"
                            onclick="saveSettings()">
                            <i class="fas fa-save"></i> LƯU
                        </button>
                    </div>
                </div>
                <div class="cc-settings-row">
                    <div class="cc-field" style="flex:2;min-width:140px">
                        <label>BIN / Prefix thẻ</label>
                        <input type="text" id="g-bin" value="515462" placeholder="6–8 chữ số" maxlength="10">
                    </div>
                    <div class="cc-field">
                        <label>Tháng (MM)</label>
                        <input type="text" id="g-mm" value="" placeholder="Trống = Ngẫu nhiên" maxlength="2">
                    </div>
                    <div class="cc-field">
                        <label>Năm (YY)</label>
                        <input type="text" id="g-yy" value="" placeholder="Trống = Ngẫu nhiên" maxlength="2">
                    </div>
                    <div class="cc-field">
                        <label>CVV</label>
                        <input type="text" id="g-cvv" value="" placeholder="Trống = Ngẫu nhiên" maxlength="4">
                    </div>
                    <div class="cc-field">
                        <label>Base IP Server</label>
                        <input type="text" id="g-ip" value="178.128.110.246" style="min-width:160px">
                    </div>
                    <div class="cc-field">
                        <label>Thread / Gate</label>
                        <div class="cc-slider-wrap">
                            <input type="range" id="g-threads" min="1" max="50" value="20"
                                oninput="document.getElementById('g-tval').textContent=this.value">
                            <span id="g-tval" style="color:var(--cc-text);font-weight:700;font-size:15px">20</span>
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
        </div>

        <!-- ── GATE PANELS ────────────────────────────────────────────────── -->
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
                    <div class="cc-stats-grid">
                        <div class="cc-stat">
                            <div class="cc-stat-num" id="s-total-<?= $gid ?>">0</div>
                            <div class="cc-stat-lbl">Tổng xử lý</div>
                        </div>
                        <div class="cc-stat">
                            <div class="cc-stat-num green" id="s-live-<?= $gid ?>">0</div>
                            <div class="cc-stat-lbl">✅ Approved</div>
                        </div>
                        <div class="cc-stat">
                            <div class="cc-stat-num red" id="s-dead-<?= $gid ?>">0</div>
                            <div class="cc-stat-lbl">❌ Declined</div>
                        </div>
                        <div class="cc-stat">
                            <div class="cc-stat-num yellow" id="s-err-<?= $gid ?>">0</div>
                            <div class="cc-stat-lbl">⚠️ Error</div>
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

                    <div class="cc-body">
                        <!-- Column 2: Live Results -->
                        <div class="cc-col cc-live-col">
                            <div class="cc-col-header cc-live-header">
                                <span>LIVE CARD</span>
                                <span id="live-count-label-<?= $gid ?>" class="badge badge-success cc-badge-norm approved">0
                                    THẺ</span>
                            </div>
                            <div class="cc-live-list" id="lives-<?= $gid ?>">
                                <div style="color:var(--cc-muted);font-size:12px;text-align:center;padding:15px 0">Chưa có
                                    thẻ LIVE nào.</div>
                            </div>
                        </div>
                    </div>

                </div><!-- /cc-gate-card -->
            </div><!-- /cc-tab-panel -->
        <?php endforeach; ?>

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
    const MAX_FEED_ITEMS = 5000;
    const MAX_LIVE_ITEMS = 5000;

    const GATES = {};
    <?php foreach ($gateways as $gid => $gate): ?>
        GATES['<?= $gid ?>'] = {
            gateId: '<?= $gid ?>',
            name: '<?= addslashes($gate['name']) ?>',
            jobId: 0,
            running: false,
            lastLive: 0,
            lives: [],
            prevChecked: 0,
            lastUpdate: 0,
        };
    <?php endforeach; ?>

    // === SETTINGS PERSISTENCE ===
    const STORAGE_KEY = 'kaishop_cc_settings';

    function saveSettings() {
        const cfg = getSettings();
        localStorage.setItem(STORAGE_KEY, JSON.stringify(cfg));
        Swal.fire({
            icon: 'success',
            title: 'Đã lưu cấu hình',
            toast: true,
            position: 'top-end',
            timer: 2000,
            showConfirmButton: false,
            background: '#fff',
            color: '#2d3436'
        });
    }

    function loadSettings() {
        try {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved) {
                const data = JSON.parse(saved);
                if (data.bin) document.getElementById('g-bin').value = data.bin;
                if (data.mm) document.getElementById('g-mm').value = data.mm === 'RN' ? '' : data.mm;
                if (data.yy) document.getElementById('g-yy').value = data.yy === 'RN' ? '' : data.yy;
                if (data.cvv) document.getElementById('g-cvv').value = data.cvv === 'RN' ? '' : data.cvv;
                if (data.ip) document.getElementById('g-ip').value = data.ip;
                if (data.threads) {
                    document.getElementById('g-threads').value = data.threads;
                    document.getElementById('g-tval').textContent = data.threads;
                }
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

    function getSettings() {
        return {
            bin: document.getElementById('g-bin').value.trim() || '515462',
            mm: document.getElementById('g-mm').value.trim().toUpperCase() || 'RN',
            yy: document.getElementById('g-yy').value.trim().toUpperCase() || 'RN',
            cvv: document.getElementById('g-cvv').value.trim().toUpperCase() || 'RN',
            batch: 5000, // Default cày số lượng lớn
            ip: document.getElementById('g-ip').value.trim() || '178.128.110.246',
            threads: parseInt(document.getElementById('g-threads').value) || 20,
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

        const cfg = getSettings();
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
            g.lastLive = 0;

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
        document.getElementById(`s-live-${gid}`).textContent = 0;
        document.getElementById(`s-dead-${gid}`).textContent = 0;
        document.getElementById(`s-err-${gid}`).textContent = 0;
        document.getElementById(`s-total-${gid}`).textContent = 0;
        document.getElementById(`badge-live-${gid}`).textContent = '0 live';
        document.getElementById(`live-count-label-${gid}`).textContent = '0 thẻ';

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

        if (activeJobIds.length === 0) return;

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
                        <div style="margin-top:8px; font-size:12px; color:var(--cc-muted); font-style:italic">
                            💬 ${esc(row.message || 'Approved')}
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

            // Update Global Stats
            document.getElementById('global-total').textContent = gTotal;
            document.getElementById('global-live').textContent = gLive;
            document.getElementById('global-dead').textContent = gDead;
            document.getElementById('global-err').textContent = gErr;

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
        const d = document.createElement('div');
        d.style.cssText = 'position:fixed;bottom:20px;right:20px;background:var(--cc-green);color:#fff;padding:12px 24px;border-radius:8px;font-weight:700;z-index:9999;box-shadow:var(--cc-shadow)';
        d.textContent = m; document.body.appendChild(d); setTimeout(() => d.remove(), 2500);
    }
    function esc(s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'); }
</script>

<?php require __DIR__ . '/../layout/foot.php'; ?>