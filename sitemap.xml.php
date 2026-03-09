<?php
header('Content-Type: application/xml; charset=utf-8');
require_once __DIR__ . '/hethong/config.php';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443 || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$fullBase = $protocol . '://' . $host;

$pages = [
    '',
    'chinh-sach',
    'dieu-khoan',
    'lien-he'
];

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <?php foreach ($pages as $page): ?>
    <url>
        <loc><?= htmlspecialchars($fullBase . url($page), ENT_QUOTES, 'UTF-8') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority><?= $page === '' ? '1.0' : '0.8' ?></priority>
    </url>
    <?php endforeach; ?>
</urlset>
