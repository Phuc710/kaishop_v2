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
        <script src="<?= asset('assets/js/fingerprint.js') ?>"></script>
        <script>
            document.addEventListener('DOMContentLoaded', async () => {
                try {
                    const fp = await KaiFingerprint.collect();
                    let fd = new FormData();
                    fd.append('fingerprint', fp.hash);
                    fd.append('fp_components', JSON.stringify(fp.components));
                    fetch('<?= url('/api/update-fingerprint') ?>', {
                        method: 'POST',
                        body: fd
                    }).catch(() => { });
                } catch (e) { }
            });
        </script>
    <?php endif; ?>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <div class="content-wrapper">