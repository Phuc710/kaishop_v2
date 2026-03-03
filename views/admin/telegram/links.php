<?php
/**
 * View: Telegram Bot - Link History
 * Route: GET /admin/telegram/links
 */
$pageTitle = 'Lịch sử liên kết Telegram';
require_once __DIR__ . '/../layout/head.php';

$breadcrumbs = [
    ['label' => 'Telegram Bot', 'url' => url('admin/telegram/settings')],
    ['label' => 'Lịch sử liên kết'],
];
require_once __DIR__ . '/../layout/breadcrumb.php';

$keyword = (string) ($keyword ?? '');
$filters = is_array($filters ?? null) ? $filters : [];
$unlinkFilter = (string) ($filters['unlink'] ?? 'all');
$period = (string) ($filters['period'] ?? 'all');
$limit = (int) ($filters['limit'] ?? 10);
$stats = is_array($stats ?? null) ? $stats : [];

$totalUsers = (int) ($stats['total_users'] ?? 0);
$totalLinked = (int) ($stats['total_linked'] ?? 0);
$totalUnlinked = (int) ($stats['total_unlinked'] ?? 0);
$filteredCount = (int) ($stats['filtered_count'] ?? count($links ?? []));
?>

<section class="content mt-3 tg-links-history-page">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6 col-xl-3 mb-3">
                <div class="card custom-card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <div class="small text-muted text-uppercase font-weight-bold">Tổng user</div>
                        <div class="h3 mb-0 font-weight-bold" id="statTotalUsers"><?= number_format($totalUsers) ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3 mb-3">
                <div class="card custom-card shadow-sm border-0 h-100 border-left-success">
                    <div class="card-body">
                        <div class="small text-muted text-uppercase font-weight-bold">Đã liên kết</div>
                        <div class="h3 mb-0 font-weight-bold text-success" id="statTotalLinked">
                            <?= number_format($totalLinked) ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3 mb-3">
                <div class="card custom-card shadow-sm border-0 h-100 border-left-secondary">
                    <div class="card-body">
                        <div class="small text-muted text-uppercase font-weight-bold">Chưa liên kết</div>
                        <div class="h3 mb-0 font-weight-bold text-secondary" id="statTotalUnlinked">
                            <?= number_format($totalUnlinked) ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3 mb-3">
                <div class="card custom-card shadow-sm border-0 h-100 border-left-primary">
                    <div class="card-body">
                        <div class="small text-muted text-uppercase font-weight-bold">Kết quả lọc</div>
                        <div class="h3 mb-0 font-weight-bold text-primary" id="statFilteredCount">
                            <?= number_format($filteredCount) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card custom-card">
            <div class="card-header border-0 pb-0">
                <h3 class="card-title text-uppercase font-weight-bold">LỊCH SỬ LIÊN KẾT TELEGRAM</h3>
            </div>

            <div class="dt-filters">
                <form id="linkFilterForm" onsubmit="return false;">
                    <div class="row g-2 justify-content-center align-items-center mb-3">
                        <div class="col-md-5 mb-2">
                            <input id="f-q" type="text" class="form-control form-control-sm"
                                value="<?= htmlspecialchars($keyword) ?>"
                                placeholder="Tìm User ID / Username / Email / Telegram...">
                        </div>
                        <div class="col-md-3 mb-2">
                            <select id="f-unlink" class="form-control form-control-sm">
                                <option value="all" <?= $unlinkFilter === 'all' ? 'selected' : '' ?>>Tất cả</option>
                                <option value="unlink" <?= $unlinkFilter === 'unlink' ? 'selected' : '' ?>>Hủy liên kết
                                </option>
                                <option value="link" <?= $unlinkFilter === 'link' ? 'selected' : '' ?>>Đã liên kết</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2 text-center">
                            <button type="button" id="btn-clear-filters" class="btn btn-danger btn-sm shadow-sm w-100">
                                Xóa lọc
                            </button>
                        </div>
                        <div class="col-md-2 mb-2 text-right"></div>
                    </div>

                    <div class="top-filter mb-2">
                        <div class="filter-show">
                            <span class="filter-label">Hiển thị :</span>
                            <select id="f-limit" class="filter-select flex-grow-1">
                                <option value="10" <?= $limit === 10 ? 'selected' : '' ?>>10</option>
                                <option value="20" <?= $limit === 20 ? 'selected' : '' ?>>20</option>
                                <option value="50" <?= $limit === 50 ? 'selected' : '' ?>>50</option>
                                <option value="100" <?= $limit === 100 ? 'selected' : '' ?>>100</option>
                            </select>
                        </div>

                        <div class="filter-short justify-content-end">
                            <span class="filter-label">Lọc theo ngày:</span>
                            <select id="f-period" class="filter-select flex-grow-1">
                                <option value="all" <?= $period === 'all' ? 'selected' : '' ?>>Tất cả</option>
                                <option value="today" <?= $period === 'today' ? 'selected' : '' ?>>Hôm nay</option>
                                <option value="7" <?= $period === '7' ? 'selected' : '' ?>>7 ngày qua</option>
                                <option value="15" <?= $period === '15' ? 'selected' : '' ?>>15 ngày qua</option>
                                <option value="30" <?= $period === '30' ? 'selected' : '' ?>>30 ngày qua</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-body pt-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-uppercase small">
                            <tr>
                                <th class="pl-4 text-center">User ID</th>
                                <th>Username Web</th>
                                <th>Email</th>
                                <th class="text-center">Telegram ID</th>
                                <th>@Username TG</th>
                                <th class="text-center">Trạng thái</th>
                                <th class="text-center">Ngày liên kết</th>
                                <th class="text-center">Hoạt động cuối</th>
                                <th class="text-right pr-4">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="linksTableBody">
                            <?php if (!empty($links)): ?>
                                <?php foreach ($links as $row): ?>
                                    <?php $hasLink = !empty($row['link_id']); ?>
                                    <tr>
                                        <td class="pl-4 text-center font-weight-bold text-muted">
                                            #<?= (int) ($row['user_id'] ?? 0) ?></td>
                                        <td class="font-weight-bold">
                                            <?= htmlspecialchars((string) ($row['web_username'] ?? '—')) ?>
                                        </td>
                                        <td><?= htmlspecialchars((string) ($row['web_email'] ?? '—')) ?></td>
                                        <td class="text-center">
                                            <?php if ($hasLink): ?>
                                                <code
                                                    class="px-2 py-1 bg-light rounded text-dark"><?= htmlspecialchars((string) ($row['telegram_id'] ?? '')) ?></code>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['telegram_username'])): ?>
                                                <a href="https://t.me/<?= htmlspecialchars((string) $row['telegram_username']) ?>"
                                                    target="_blank" class="font-weight-bold">
                                                    @<?= htmlspecialchars((string) $row['telegram_username']) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($hasLink): ?>
                                                <span class="badge badge-success">Link</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Unlink</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center small text-muted">
                                            <?= !empty($row['linked_at']) ? FormatHelper::eventTime($row['linked_at'], $row['linked_at']) : '—' ?>
                                        </td>
                                        <td class="text-center small">
                                            <?php if (!empty($row['last_active'])): ?>
                                                <span class="text-success font-weight-bold">
                                                    <?= FormatHelper::eventTime($row['last_active'], $row['last_active']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right pr-4">
                                            <?php if ($hasLink): ?>
                                                <button type="button" class="btn btn-outline-danger btn-sm px-3 btn-unlink-user"
                                                    data-user-id="<?= (int) ($row['user_id'] ?? 0) ?>"
                                                    data-username="<?= htmlspecialchars((string) ($row['web_username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                    <i class="fas fa-unlink mr-1"></i> Hủy liên kết
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted small">Không áp dụng</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-muted">Không có dữ liệu phù hợp với bộ lọc.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    #linkFilterForm .form-control {
        min-height: 38px;
    }

    @media (max-width: 991.98px) {
        #linkFilterForm .top-filter {
            flex-direction: column;
            align-items: stretch;
        }

        #linkFilterForm .filter-show,
        #linkFilterForm .filter-short {
            width: 100%;
        }
    }
</style>

<script>
    (function () {
        const endpoint = '<?= url('admin/telegram/links') ?>';
        const bodyEl = document.getElementById('linksTableBody');
        const qEl = document.getElementById('f-q');
        const unlinkEl = document.getElementById('f-unlink');
        const limitEl = document.getElementById('f-limit');
        const periodEl = document.getElementById('f-period');
        const clearBtn = document.getElementById('btn-clear-filters');
        const statTotalUsers = document.getElementById('statTotalUsers');
        const statTotalLinked = document.getElementById('statTotalLinked');
        const statTotalUnlinked = document.getElementById('statTotalUnlinked');
        const statFilteredCount = document.getElementById('statFilteredCount');

        let searchTimer = null;
        let currentController = null;

        function escapeHtml(str) {
            return String(str || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function formatDateTime(dt) {
            if (!dt) return '—';
            const normalized = String(dt).replace('T', ' ');
            return normalized.length >= 19 ? normalized.slice(0, 19) : normalized;
        }

        function buildQueryParams() {
            const p = new URLSearchParams();
            const q = qEl ? qEl.value.trim() : '';
            const unlink = unlinkEl ? unlinkEl.value : 'all';
            const limit = limitEl ? limitEl.value : '10';
            const period = periodEl ? periodEl.value : 'all';

            if (q !== '') p.set('q', q);
            if (unlink && unlink !== 'all') p.set('unlink', unlink);
            if (period && period !== 'all') p.set('period', period);
            p.set('limit', limit || '10');
            return p;
        }

        function updateStats(stats) {
            if (!stats) return;
            if (statTotalUsers) statTotalUsers.textContent = Number(stats.total_users || 0).toLocaleString();
            if (statTotalLinked) statTotalLinked.textContent = Number(stats.total_linked || 0).toLocaleString();
            if (statTotalUnlinked) statTotalUnlinked.textContent = Number(stats.total_unlinked || 0).toLocaleString();
            if (statFilteredCount) statFilteredCount.textContent = Number(stats.filtered_count || 0).toLocaleString();
        }

        function renderRows(rows) {
            if (!bodyEl) return;
            if (!Array.isArray(rows) || rows.length === 0) {
                bodyEl.innerHTML = '<tr><td colspan="9" class="text-center py-5 text-muted">Không có dữ liệu phù hợp với bộ lọc.</td></tr>';
                return;
            }

            bodyEl.innerHTML = rows.map((row) => {
                const hasLink = !!row.link_id;
                const userId = Number(row.user_id || 0);
                const webUsername = escapeHtml(row.web_username || '—');
                const webEmail = escapeHtml(row.web_email || '—');
                const telegramId = escapeHtml(row.telegram_id || '');
                const telegramUsername = String(row.telegram_username || '');
                const linkedAt = formatDateTime(row.linked_at);
                const lastActive = formatDateTime(row.last_active);

                const telegramIdHtml = hasLink
                    ? '<code class="px-2 py-1 bg-light rounded text-dark">' + telegramId + '</code>'
                    : '<span class="text-muted">—</span>';

                const telegramUsernameHtml = telegramUsername
                    ? '<a href="https://t.me/' + encodeURIComponent(telegramUsername) + '" target="_blank" class="font-weight-bold">@' + escapeHtml(telegramUsername) + '</a>'
                    : '<span class="text-muted small">—</span>';

                const statusHtml = hasLink
                    ? '<span class="badge badge-success">Link</span>'
                    : '<span class="badge badge-secondary">Unlink</span>';

                const actionHtml = hasLink
                    ? '<button type="button" class="btn btn-outline-danger btn-sm px-3 btn-unlink-user" data-user-id="' + userId + '" data-username="' + escapeHtml(row.web_username || '') + '"><i class="fas fa-unlink mr-1"></i> Hủy liên kết</button>'
                    : '<span class="text-muted small">Không áp dụng</span>';

                return '' +
                    '<tr>' +
                    '<td class="pl-4 text-center font-weight-bold text-muted">#' + userId + '</td>' +
                    '<td class="font-weight-bold">' + webUsername + '</td>' +
                    '<td>' + webEmail + '</td>' +
                    '<td class="text-center">' + telegramIdHtml + '</td>' +
                    '<td>' + telegramUsernameHtml + '</td>' +
                    '<td class="text-center">' + statusHtml + '</td>' +
                    '<td class="text-center small text-muted">' + linkedAt + '</td>' +
                    '<td class="text-center small"><span class="' + (row.last_active ? 'text-success font-weight-bold' : 'text-muted') + '">' + lastActive + '</span></td>' +
                    '<td class="text-right pr-4">' + actionHtml + '</td>' +
                    '</tr>';
            }).join('');
        }

        async function fetchLinks(pushUrl = true) {
            const params = buildQueryParams();
            const url = endpoint + '?' + params.toString();

            if (currentController) currentController.abort();
            currentController = new AbortController();

            try {
                const res = await fetch(url, {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    signal: currentController.signal
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                if (!data || data.success !== true) throw new Error('Invalid response');

                renderRows(data.rows || []);
                updateStats(data.stats || {});

                if (pushUrl && history.replaceState) {
                    history.replaceState(null, '', url);
                }
            } catch (err) {
                if (err && err.name === 'AbortError') return;
                SwalHelper.toast('Không thể tải dữ liệu', 'error');
            }
        }

        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('.btn-unlink-user');
            if (!btn) return;
            const userId = Number(btn.getAttribute('data-user-id') || '0');
            const username = String(btn.getAttribute('data-username') || '');
            if (!userId) return;
            await unlinkUser(userId, username);
        });

        if (qEl) {
            qEl.addEventListener('input', () => {
                if (searchTimer) clearTimeout(searchTimer);
                searchTimer = setTimeout(() => fetchLinks(), 350);
            });
        }
        if (unlinkEl) unlinkEl.addEventListener('change', () => fetchLinks());
        if (limitEl) limitEl.addEventListener('change', () => fetchLinks());
        if (periodEl) periodEl.addEventListener('change', () => fetchLinks());
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                if (qEl) qEl.value = '';
                if (unlinkEl) unlinkEl.value = 'all';
                if (limitEl) limitEl.value = '10';
                if (periodEl) periodEl.value = 'all';
                fetchLinks();
            });
        }

        window.unlinkUser = async function (userId, username) {
            const target = username ? ('"' + username + '"') : ('#' + userId);
            SwalHelper.confirm(
                'Xác nhận Hủy liên kết?',
                'Tài khoản ' + target + ' sẽ bị hủy liên kết Telegram.',
                async () => {
                    try {
                        const body = new URLSearchParams({ user_id: String(userId) });
                        const res = await fetch('<?= url('admin/telegram/links/unlink') ?>', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                                'Accept': 'application/json'
                            },
                            body
                        });

                        const data = await res.json();
                        SwalHelper.toast(data.message || 'Không thể xử lý yêu cầu', data.success ? 'success' : 'error');
                        if (data.success) fetchLinks(false);
                    } catch (err) {
                        SwalHelper.toast('Lỗi kết nối máy chủ', 'error');
                    }
                }
            );
        };
    })();
</script>

<?php require_once __DIR__ . '/../layout/foot.php'; ?>