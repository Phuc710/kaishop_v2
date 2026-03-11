<?php
header('Content-Type: application/xml; charset=utf-8');
require_once __DIR__ . '/hethong/config.php';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443 || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$fullBase = $protocol . '://' . $host;

// Static pages
$staticPages = [
    ['loc' => '', 'changefreq' => 'daily', 'priority' => '1.0'],
    ['loc' => 'lien-he', 'changefreq' => 'monthly', 'priority' => '0.6'],
    ['loc' => 'chinh-sach', 'changefreq' => 'monthly', 'priority' => '0.6'],
    ['loc' => 'dieu-khoan', 'changefreq' => 'monthly', 'priority' => '0.6'],
];

// Fetch active products from DB
$productUrls = [];
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("
        SELECT id, public_path, slug, updated_at
        FROM products
        WHERE status = 'ON'
        ORDER BY updated_at DESC
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as $p) {
        $path = trim((string) ($p['public_path'] ?? ''));
        if ($path === '') {
            $path = 'product/' . (int) $p['id'];
        }
        $lastmod = '';
        if (!empty($p['updated_at'])) {
            $ts = strtotime((string) $p['updated_at']);
            if ($ts) {
                $lastmod = date('Y-m-d', $ts);
            }
        }
        $productUrls[] = [
            'loc' => $path,
            'lastmod' => $lastmod ?: date('Y-m-d'),
            'changefreq' => 'daily',
            'priority' => '0.8',
        ];
    }
} catch (Throwable $e) {
    // Silently skip product URLs if DB fails
    $productUrls = [];
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
    <?php foreach ($staticPages as $page): ?>
        <url>
            <loc><?= htmlspecialchars($fullBase . url($page['loc']), ENT_QUOTES, 'UTF-8') ?></loc>
            <lastmod><?= date('Y-m-d') ?></lastmod>
            <changefreq><?= $page['changefreq'] ?></changefreq>
            <priority><?= $page['priority'] ?></priority>
        </url>
    <?php endforeach; ?>

    <?php foreach ($productUrls as $p): ?>
        <url>
            <loc><?= htmlspecialchars($fullBase . url($p['loc']), ENT_QUOTES, 'UTF-8') ?></loc>
            <lastmod><?= htmlspecialchars($p['lastmod'], ENT_QUOTES, 'UTF-8') ?></lastmod>
            <changefreq><?= $p['changefreq'] ?></changefreq>
            <priority><?= $p['priority'] ?></priority>
        </url>
    <?php endforeach; ?>
</urlset>