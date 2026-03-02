<?php
/**
 * View: Telegram Bot — Terminal Log
 * Route: admin/telegram/terminal
 */
$pageTitle = '⚡ Bot Terminal';
require_once __DIR__ . '/../layout/head.php';

$breadcrumbs = [
    ['label' => 'Telegram Bot', 'url' => url('admin/telegram/settings')],
    ['label' => 'Bot Terminal'],
];
require_once __DIR__ . '/../layout/breadcrumb.php';
?>

<link rel="stylesheet" href="<?= asset('assets/css/telegram_admin.css') ?>">

<style>
    /* ─── Terminal Shell ─────────────────────────────────────────── */
    .tg-terminal-wrap {
        display: flex;
        flex-direction: column;
        height: calc(100vh - 160px);
        min-height: 400px;
        background: #0d1117;
        border-radius: 14px;
        overflow: hidden;
        border: 1.5px solid #21262d;
        box-shadow: 0 8px 48px rgba(0, 0, 0, .5);
        font-family: 'JetBrains Mono', 'Fira Code', 'Consolas', 'Courier New', monospace;
    }

    .tg-terminal-topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 16px;
        background: #161b22;
        border-bottom: 1px solid #21262d;
        flex-shrink: 0;
    }

    .tg-terminal-dots {
        display: flex;
        gap: 6px;
    }

    .tg-terminal-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }

    .tg-terminal-dot--red {
        background: #ff5f57;
    }

    .tg-terminal-dot--amber {
        background: #febc2e;
    }

    .tg-terminal-dot--green {
        background: #28c840;
    }

    .tg-terminal-title {
        color: #8b949e;
        font-size: 12px;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    .tg-terminal-actions {
        display: flex;
        gap: 8px;
    }

    .tg-terminal-body {
        flex: 1;
        overflow-y: auto;
        padding: 14px 20px;
        color: #c9d1d9;
        font-size: 12.5px;
        line-height: 1.7;
        scroll-behavior: smooth;
    }

    .tg-terminal-body::-webkit-scrollbar {
        width: 6px;
    }

    .tg-terminal-body::-webkit-scrollbar-track {
        background: transparent;
    }

    .tg-terminal-body::-webkit-scrollbar-thumb {
        background: #30363d;
        border-radius: 3px;
    }

    /* ─── Log rows ──────────────────────────────────────────────── */
    .tg-log-row {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        padding: 2px 0;
        border-bottom: 1px solid rgba(33, 38, 45, .5);
        transition: background .15s;
    }

    .tg-log-row:hover {
        background: rgba(255, 255, 255, .03);
    }

    .tg-log-ts {
        color: #484f58;
        white-space: nowrap;
        min-width: 78px;
    }

    .tg-log-lvl {
        min-width: 46px;
        font-weight: 700;
        text-align: center;
        border-radius: 4px;
        padding: 0 4px;
    }

    .tg-log-lvl--INFO {
        color: #58a6ff;
    }

    .tg-log-lvl--WARN {
        color: #e3b341;
    }

    .tg-log-lvl--ERROR {
        color: #f85149;
    }

    .tg-log-dir {
        min-width: 60px;
        font-size: 10px;
        opacity: .7;
    }

    .tg-log-dir--IN {
        color: #3fb950;
    }

    .tg-log-dir--OUT {
        color: #58a6ff;
    }

    .tg-log-cat {
        min-width: 80px;
        color: #8b949e;
        font-size: 10px;
    }

    .tg-log-msg {
        flex: 1;
        word-break: break-word;
        color: #e6edf3;
    }

    .tg-log-data {
        margin-top: 4px;
        margin-left: 278px;
        color: #8b949e;
        font-size: 11px;
        white-space: pre-wrap;
        background: #161b22;
        border-left: 2px solid #30363d;
        padding: 4px 8px;
        border-radius: 0 4px 4px 0;
    }

    /* ─── Status bar ───────────────────────────────────────────── */
    .tg-terminal-statusbar {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 6px 16px;
        background: #161b22;
        border-top: 1px solid #21262d;
        font-size: 11px;
        color: #484f58;
        flex-shrink: 0;
    }

    .tg-status-pill-sm {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        border-radius: 20px;
        padding: 2px 10px;
        font-size: 10px;
        font-weight: 600;
        letter-spacing: .5px;
    }

    .tg-status-pill-sm--live {
        background: #1a3524;
        color: #3fb950;
    }

    .tg-status-pill-sm--idle {
        background: #21262d;
        color: #8b949e;
    }

    .tg-terminal-blink {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #3fb950;
        animation: blink-dot 1.2s infinite;
    }

    @keyframes blink-dot {

        0%,
        100% {
            opacity: 1
        }

        50% {
            opacity: .15
        }
    }

    .tg-empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        opacity: .4;
        gap: 8px;
    }

    .tg-empty-state i {
        font-size: 2.5rem;
        color: #30363d;
    }

    .tg-empty-state p {
        color: #484f58;
        font-size: 13px;
    }
</style>

<section class="content">
    <div class="container-fluid py-2">

        <div class="tg-terminal-wrap">

            <!-- Top bar -->
            <div class="tg-terminal-topbar">
                <div class="tg-terminal-dots">
                    <span class="tg-terminal-dot tg-terminal-dot--red"></span>
                    <span class="tg-terminal-dot tg-terminal-dot--amber"></span>
                    <span class="tg-terminal-dot tg-terminal-dot--green"></span>
                </div>
                <span class="tg-terminal-title">
                    <i class="fas fa-bolt mr-1"></i> Telegram Bot Terminal — Live Activity Log
                </span>
                <div class="tg-terminal-actions">
                    <button class="btn btn-sm btn-outline-secondary" id="btnClearView"
                        style="color:#8b949e;border-color:#30363d;font-size:11px;"
                        title="Xóa màn hình terminal (không xóa DB)">
                        <i class="fas fa-eraser mr-1"></i> Clear
                    </button>
                    <button class="btn btn-sm" id="btnScrollBottom"
                        style="background:#21262d;color:#58a6ff;border:none;font-size:11px;" title="Cuộn xuống cuối">
                        <i class="fas fa-arrow-down"></i>
                    </button>
                </div>
            </div>

            <!-- Log body -->
            <div class="tg-terminal-body" id="terminalBody">
                <?php if (empty($logs)): ?>
                    <div class="tg-empty-state">
                        <i class="fas fa-satellite-dish"></i>
                        <p>Chưa có log nào. Bot sẽ xuất hiện tại đây khi nhận được tin nhắn...</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($logs as $row): ?>
                        <?= renderLogRow($row) ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Status bar -->
            <div class="tg-terminal-statusbar">
                <span class="tg-status-pill-sm tg-status-pill-sm--live" id="liveStatusPill">
                    <span class="tg-terminal-blink"></span> LIVE
                </span>
                <span id="logCountLabel">Hiển thị <strong id="logCount">
                        <?= count($logs) ?>
                    </strong> dòng</span>
                <span style="margin-left:auto;" id="lastPollLabel">Cập nhật lúc <span id="lastPollTime">—</span></span>
            </div>

        </div>

    </div>
</section>

<?php
function renderLogRow(array $row, bool $echo = false): string
{
    $ts = date('H:i:s', strtotime($row['created_at'] ?? ''));
    $lvl = htmlspecialchars($row['level'] ?? 'INFO');
    $dir = ($row['type'] ?? 'INCOMING') === 'INCOMING' ? 'IN' : 'OUT';
    $cat = htmlspecialchars(strtoupper($row['category'] ?? 'GEN'));
    $msg = htmlspecialchars($row['message'] ?? '');
    $data = trim((string) ($row['data'] ?? ''));

    $dataHtml = '';
    if ($data !== '') {
        // Pretty-print JSON if applicable
        $decoded = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $data = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        $dataHtml = '<div class="tg-log-data">' . htmlspecialchars($data) . '</div>';
    }

    return <<<HTML
<div class="tg-log-row" data-id="{$row['id']}">
    <span class="tg-log-ts">{$ts}</span>
    <span class="tg-log-lvl tg-log-lvl--{$lvl}">[{$lvl}]</span>
    <span class="tg-log-dir tg-log-dir--{$dir}">&gt; {$dir}</span>
    <span class="tg-log-cat">{$cat}</span>
    <span class="tg-log-msg">
        {$msg}
        {$dataHtml}
    </span>
</div>
HTML;
}
?>

<script>
    (function () {
        const body = document.getElementById('terminalBody');
        const countEl = document.getElementById('logCount');
        const timeEl = document.getElementById('lastPollTime');
        const liveEl = document.getElementById('liveStatusPill');
        const POLL_MS = 2500; // poll every 2.5s

        let maxId = <?= (int) ($maxId ?? 0) ?>;
        let logCount = <?= count($logs) ?>;
        let autoScroll = true;

        // ─── Auto-scroll tracking ──────────────────────────────────
        body.addEventListener('scroll', () => {
            autoScroll = body.scrollTop + body.clientHeight >= body.scrollHeight - 30;
        });

        function scrollDown() {
            body.scrollTop = body.scrollHeight;
        }
        scrollDown();

        document.getElementById('btnScrollBottom').addEventListener('click', scrollDown);

        document.getElementById('btnClearView').addEventListener('click', () => {
            // Remove all dg-empty-state if exists
            body.querySelectorAll('.tg-empty-state').forEach(el => el.remove());
            body.querySelectorAll('.tg-log-row').forEach(el => el.remove());
            logCount = 0;
            countEl.textContent = 0;
        });

        // ─── Color by level ────────────────────────────────────────
        function levelClass(lvl) {
            return { INFO: 'tg-log-lvl--INFO', WARN: 'tg-log-lvl--WARN', ERROR: 'tg-log-lvl--ERROR' }[lvl] || 'tg-log-lvl--INFO';
        }

        function renderRow(r) {
            const ts = r.created_at ? r.created_at.substr(11, 8) : '--:--:--';
            const lvl = r.level || 'INFO';
            const dir = r.type === 'OUTGOING' ? 'OUT' : 'IN';
            const cat = (r.category || 'GEN').toUpperCase();

            let dataHtml = '';
            if (r.data && r.data.trim() !== '') {
                let pretty = r.data;
                try {
                    pretty = JSON.stringify(JSON.parse(r.data), null, 2);
                } catch (e) { }
                dataHtml = `<div class="tg-log-data">${escHtml(pretty)}</div>`;
            }

            const div = document.createElement('div');
            div.className = 'tg-log-row';
            div.dataset.id = r.id;
            div.innerHTML = `
            <span class="tg-log-ts">${escHtml(ts)}</span>
            <span class="tg-log-lvl ${levelClass(lvl)}">[${escHtml(lvl)}]</span>
            <span class="tg-log-dir tg-log-dir--${dir}">&gt; ${dir}</span>
            <span class="tg-log-cat">${escHtml(cat)}</span>
            <span class="tg-log-msg">${escHtml(r.message || '')}${dataHtml}</span>
        `;
            return div;
        }

        function escHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        // ─── Polling ───────────────────────────────────────────────
        let offline = false;

        async function poll() {
            try {
                const res = await fetch(`<?= url('admin/telegram/terminal/poll') ?>?after=${maxId}&_t=${Date.now()}`);
                const data = await res.json();

                if (offline) {
                    offline = false;
                    liveEl.className = 'tg-status-pill-sm tg-status-pill-sm--live';
                    liveEl.innerHTML = '<span class="tg-terminal-blink"></span> LIVE';
                }

                if (data.rows && data.rows.length > 0) {
                    // Remove empty-state placeholder if shown
                    body.querySelectorAll('.tg-empty-state').forEach(el => el.remove());

                    data.rows.forEach(r => {
                        body.appendChild(renderRow(r));
                        logCount++;
                    });
                    maxId = data.maxId || maxId;
                    countEl.textContent = logCount;
                    if (autoScroll) scrollDown();
                }

                const now = new Date();
                timeEl.textContent = `${now.getHours().toString().padStart(2, '0')}:${now.getMinutes().toString().padStart(2, '0')}:${now.getSeconds().toString().padStart(2, '0')}`;

            } catch (e) {
                if (!offline) {
                    offline = true;
                    liveEl.className = 'tg-status-pill-sm tg-status-pill-sm--idle';
                    liveEl.innerHTML = '⚠ OFFLINE';
                }
            }
            setTimeout(poll, POLL_MS);
        }

        poll();
    })();
</script>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>