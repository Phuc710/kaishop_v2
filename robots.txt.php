<?php
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443 || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$fullSitemapUrl = $protocol . '://' . $host . url('sitemap.xml');
?>
User-agent: *
Allow: /
Allow: /assets/
Allow: /public/
Disallow: /admin/
Disallow: /profile/
Disallow: /deposit/
Disallow: /history-balance/
Disallow: /history-code/
Disallow: /history-orders/
Disallow: /login
Disallow: /register
Disallow: /password-reset/

Sitemap: <?= $fullSitemapUrl ?>
