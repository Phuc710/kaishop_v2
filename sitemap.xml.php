<?php
header('Content-Type: application/xml; charset=utf-8');
require_once __DIR__ . '/hethong/config.php';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443 || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$fullBase = $protocol . '://' . $host;

$resolveLastmod = static function (array $paths): string {
    $latestTs = 0;
    foreach ($paths as $path) {
        if (!is_string($path) || $path === '' || !file_exists($path)) {
            continue;
        }

        $mtime = @filemtime($path);
        if ($mtime && $mtime > $latestTs) {
            $latestTs = $mtime;
        }
    }

    return $latestTs > 0 ? date('Y-m-d', $latestTs) : date('Y-m-d');
};

$staticPages = [
    [
        'loc' => '',
        'lastmod' => $resolveLastmod([
            __DIR__ . '/app/Controllers/HomeController.php',
            __DIR__ . '/views/home/index.php',
            __DIR__ . '/hethong/head2.php',
        ]),
        'changefreq' => 'daily',
        'priority' => '1.0',
    ],
    [
        'loc' => 'lien-he',
        'lastmod' => $resolveLastmod([
            __DIR__ . '/app/Controllers/ContactController.php',
            __DIR__ . '/views/contact/index.php',
            __DIR__ . '/hethong/head2.php',
        ]),
        'changefreq' => 'monthly',
        'priority' => '0.6',
    ],
    [
        'loc' => 'chinh-sach',
        'lastmod' => $resolveLastmod([
            __DIR__ . '/app/Controllers/PolicyController.php',
            __DIR__ . '/views/policy/index.php',
            __DIR__ . '/assets/css/policy.css',
        ]),
        'changefreq' => 'monthly',
        'priority' => '0.6',
    ],
    [
        'loc' => 'dieu-khoan',
        'lastmod' => $resolveLastmod([
            __DIR__ . '/app/Controllers/TermsController.php',
            __DIR__ . '/views/terms/index.php',
            __DIR__ . '/assets/css/policy.css',
        ]),
        'changefreq' => 'monthly',
        'priority' => '0.6',
    ],
];

$productUrls = [];
$categoryUrls = [];
try {
    $db = Database::getInstance()->getConnection();

    $hasShowOnWeb = false;
    $showOnWebStmt = $db->query("SHOW COLUMNS FROM products LIKE 'show_on_web'");
    if ($showOnWebStmt) {
        $hasShowOnWeb = (bool) $showOnWebStmt->fetch(PDO::FETCH_ASSOC);
    }

    $productVisibilitySql = $hasShowOnWeb
        ? "p.status = 'ON' AND p.show_on_web = 1"
        : "p.status = 'ON'";

    $catStmt = $db->query("
        SELECT c.slug, MAX(p.updated_at) AS lastmod
        FROM categories c
        INNER JOIN products p
            ON p.category_id = c.id
           AND {$productVisibilitySql}
        WHERE c.status = 'ON'
          AND c.slug IS NOT NULL
          AND TRIM(c.slug) <> ''
        GROUP BY c.id, c.slug, c.display_order
        ORDER BY c.display_order ASC, c.id DESC
    ");
    $categories = $catStmt ? $catStmt->fetchAll(PDO::FETCH_ASSOC) : [];
    foreach ($categories as $cat) {
        $slug = trim((string) ($cat['slug'] ?? ''));
        if ($slug === '') {
            continue;
        }

        $lastmod = !empty($cat['lastmod']) && strtotime((string) $cat['lastmod'])
            ? date('Y-m-d', strtotime((string) $cat['lastmod']))
            : date('Y-m-d');

        $categoryUrls[] = [
            'loc' => 'category/' . $slug,
            'lastmod' => $lastmod,
            'changefreq' => 'weekly',
            'priority' => '0.7',
        ];
    }

    $stmt = $db->query("
        SELECT p.id, p.public_path, p.name, p.image, p.updated_at
        FROM products p
        WHERE {$productVisibilitySql}
        ORDER BY p.updated_at DESC
    ");
    $products = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    foreach ($products as $p) {
        $path = trim((string) ($p['public_path'] ?? ''));
        if ($path === '') {
            $path = 'product/' . (int) $p['id'];
        }

        $lastmod = !empty($p['updated_at']) && strtotime((string) $p['updated_at'])
            ? date('Y-m-d', strtotime((string) $p['updated_at']))
            : date('Y-m-d');

        $productUrls[] = [
            'loc' => $path,
            'lastmod' => $lastmod,
            'image' => trim((string) ($p['image'] ?? '')),
            'name' => trim((string) ($p['name'] ?? '')),
            'changefreq' => 'daily',
            'priority' => '0.8',
        ];
    }
} catch (Throwable $e) {
    $categoryUrls = [];
    $productUrls = [];
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
    <?php foreach ($staticPages as $page): ?>
        <url>
            <loc><?= htmlspecialchars($fullBase . url($page['loc']), ENT_QUOTES, 'UTF-8') ?></loc>
            <lastmod><?= htmlspecialchars($page['lastmod'], ENT_QUOTES, 'UTF-8') ?></lastmod>
            <changefreq><?= $page['changefreq'] ?></changefreq>
            <priority><?= $page['priority'] ?></priority>
        </url>
    <?php endforeach; ?>

    <?php foreach ($categoryUrls as $cat): ?>
        <url>
            <loc><?= htmlspecialchars($fullBase . url($cat['loc']), ENT_QUOTES, 'UTF-8') ?></loc>
            <lastmod><?= htmlspecialchars($cat['lastmod'], ENT_QUOTES, 'UTF-8') ?></lastmod>
            <changefreq><?= $cat['changefreq'] ?></changefreq>
            <priority><?= $cat['priority'] ?></priority>
        </url>
    <?php endforeach; ?>

    <?php foreach ($productUrls as $p): ?>
        <url>
            <loc><?= htmlspecialchars($fullBase . url($p['loc']), ENT_QUOTES, 'UTF-8') ?></loc>
            <?php if (!empty($p['image'])): ?>
                <image:image>
                    <image:loc>
                        <?= htmlspecialchars(filter_var($p['image'], FILTER_VALIDATE_URL) ? $p['image'] : $fullBase . '/' . ltrim($p['image'], '/'), ENT_QUOTES, 'UTF-8') ?>
                    </image:loc>
                    <image:title><?= htmlspecialchars($p['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></image:title>
                </image:image>
            <?php endif; ?>
            <lastmod><?= htmlspecialchars($p['lastmod'], ENT_QUOTES, 'UTF-8') ?></lastmod>
            <changefreq><?= $p['changefreq'] ?></changefreq>
            <priority><?= $p['priority'] ?></priority>
        </url>
    <?php endforeach; ?>
</urlset>