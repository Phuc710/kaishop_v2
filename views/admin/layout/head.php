<?php
/**
 * Admin Layout — HEAD wrapper
 * 
 * File này bọc toàn bộ HTML cho mọi trang admin OOP.
 * Trước khi include, set biến $pageTitle = 'Tên trang';
 * 
 * Bao gồm: <!DOCTYPE html>, <head>, <body>, sidebar nav
 */
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require_once dirname(__DIR__, 3) . '/admin/head.php'; ?>
    <title><?= htmlspecialchars($pageTitle ?? 'Admin Panel') ?> | Admin</title>
    <?php require_once dirname(__DIR__, 3) . '/admin/nav.php'; ?>

    <?php if (isset($user['id'])): ?>
        <script src="<?= asset('assets/js/fingerprint.js') ?>" defer></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const runFingerprintSync = async () => {
                    try {
                        if (typeof KaiFingerprint === 'undefined') return;

                        const storageKey = 'ks_admin_fp_synced_at';
                        const now = Date.now();
                        const lastRun = parseInt(localStorage.getItem(storageKey) || '0', 10);
                        if (!isNaN(lastRun) && (now - lastRun) < (15 * 60 * 1000)) {
                            return;
                        }

                        const fp = await KaiFingerprint.collect();
                        const fd = new FormData();
                        fd.append('fingerprint', fp.hash);
                        fd.append('fp_components', JSON.stringify(fp.components));
                        fetch('<?= url('/api/update-fingerprint') ?>', {
                            method: 'POST',
                            body: fd,
                            keepalive: true
                        }).catch(() => { });
                        localStorage.setItem(storageKey, String(now));
                    } catch (e) { }
                };

                if ('requestIdleCallback' in window) {
                    requestIdleCallback(() => { runFingerprintSync(); }, { timeout: 2500 });
                } else {
                    setTimeout(runFingerprintSync, 1200);
                }
            });
        </script>
    <?php endif; ?>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <div class="content-wrapper">
