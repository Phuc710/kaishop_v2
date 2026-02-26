<?php
if (!defined('BASE_URL')) {
    die('Direct access not permitted');
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title>
        <?= htmlspecialchars($seoTitle) ?>
    </title>
    
    <style>
        .contact-section {
            background-color: #f9fafb;
            min-height: 80vh;
        }

        .contact-grid {
            background: #ffffff;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            height: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #f3f4f6;
        }

        .contact-grid:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .contact-icon {
            width: 60px;
            height: 60px;
            background: #fffafa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid #fff5f5;
        }

        .contact-icon img {
            width: 28px;
            height: 28px;
        }
        
        .contact-icon i {
            font-size: 24px;
            color: #4b5563;
        }

        .contact-details h6 {
            font-weight: 700;
            color: #4b5563;
            margin-bottom: 12px;
            font-size: 1.1rem;
        }

        .contact-details p {
            margin-bottom: 0;
            color: #1f2937;
            font-weight: 600;
        }

        .contact-details a {
            color: #1f2937;
            text-decoration: none;
            transition: color 0.2s;
        }

        .contact-details a:hover {
            color: #0683a4;
        }

        .contact-bottom {
            background: #fff;
            padding: 60px 0;
        }
    </style>

    <!-- Schema.org Markup for ContactPage -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "ContactPage",
      "name": "<?= htmlspecialchars($seoTitle) ?>",
      "description": "<?= htmlspecialchars($seoDescription) ?>",
      "url": "<?= url('lien-he') ?>",
      "mainEntity": {
        "@type": "Organization",
        "name": "<?= htmlspecialchars($siteName) ?>",
        "email": "<?= htmlspecialchars($contactEmail) ?>",
        "telephone": "<?= htmlspecialchars($contactPhone) ?>",
        "sameAs": [
          <?php
          $links = [];
          foreach ($socialItems as $s) {
              if (!empty($s['value'])) {
                  $href = preg_match('~^https?://~i', $s['value']) ? $s['value'] : ('https://' . ltrim($s['value'], '/'));
                  $links[] = '"' . htmlspecialchars($href) . '"';
              }
          }
          echo implode(",\n          ", $links);
          ?>
        ]
      }
    }
    </script>
</head>

<body>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>

    <main>
        <section class="contact-section py-4">
            <div class="contact-bottom bg-white">
                <div class="container">
                    <div class="row justify-content-center mb-5">
                        <div class="col-lg-9 text-center">
                            <h1 class="mb-3" style="font-weight:700; color:#1f2937; font-size: 2.8rem;">
                                <?= htmlspecialchars($pageTitle) ?>
                            </h1>
                            <?php if ($pageSubtitle !== ''): ?>
                                <p class="text-muted mb-2" style="font-size: 1.1rem;">
                                    <?= htmlspecialchars($pageSubtitle) ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($supportNote !== ''): ?>
                                <p class="mb-0" style="color:#0f766e; font-weight:600;">
                                    <?= htmlspecialchars($supportNote) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row justify-content-center">
                        <?php if ($contactEmail !== ''): ?>
                            <div class="col-lg-4 col-md-6 d-flex mb-4">
                                <div class="contact-grid w-100">
                                    <div class="contact-content">
                                        <div class="contact-icon">
                                            <span>
                                                <i class="fa-solid fa-envelope" style="color: #f97316;"></i>
                                            </span>
                                        </div>
                                        <div class="contact-details">
                                            <h6>
                                                <?= htmlspecialchars($contactEmailLabel) ?>
                                            </h6>
                                            <p style="word-break: break-all;">
                                                <a href="mailto:<?= htmlspecialchars($contactEmail) ?>">
                                                    <?= htmlspecialchars($contactEmail) ?>
                                                </a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($contactPhone !== ''): ?>
                            <?php $phoneHref = preg_replace('/[^0-9+]/', '', $contactPhone) ?: $contactPhone; ?>
                            <div class="col-lg-4 col-md-6 d-flex mb-4">
                                <div class="contact-grid w-100">
                                    <div class="contact-content">
                                        <div class="contact-icon">
                                            <span>
                                                <i class="fa-solid fa-phone" style="color: #f59e0b;"></i>
                                            </span>
                                        </div>
                                        <div class="contact-details">
                                            <h6>
                                                <?= htmlspecialchars($contactPhoneLabel) ?>
                                            </h6>
                                            <p>
                                                <a href="tel:<?= htmlspecialchars($phoneHref) ?>">
                                                    <?= htmlspecialchars($contactPhone) ?>
                                                </a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($socialItems as $item): ?>
                            <?php $socialValue = trim((string) ($item['value'] ?? '')); ?>
                            <?php if ($socialValue === '')
                                continue; ?>
                            <?php
                            $socialHref = preg_match('~^https?://~i', $socialValue) ? $socialValue : ('https://' . ltrim($socialValue, '/'));
                            $iconColor = '#4b5563';
                            if (stripos($item['label'], 'Facebook') !== false) $iconColor = '#1877f2';
                            if (stripos($item['label'], 'Telegram') !== false) $iconColor = '#0088cc';
                            if (stripos($item['label'], 'TikTok') !== false) $iconColor = '#000000';
                            if (stripos($item['label'], 'YouTube') !== false) $iconColor = '#ff0000';
                            ?>
                            <div class="col-lg-4 col-md-6 d-flex mb-4">
                                <div class="contact-grid w-100">
                                    <div class="contact-content">
                                        <div class="contact-icon">
                                            <span>
                                                <i class="<?= htmlspecialchars((string) ($item['icon_class'] ?? 'fa-solid fa-link')) ?>"
                                                    style="color: <?= $iconColor ?>;"></i>
                                            </span>
                                        </div>
                                        <div class="contact-details">
                                            <h6 style="color: #4b5563;">
                                                <?= htmlspecialchars((string) $item['label']) ?>
                                            </h6>
                                            <p style="word-break: break-all;">
                                                <a href="<?= htmlspecialchars($socialHref) ?>" target="_blank"
                                                    rel="noopener noreferrer">
                                                    <?= htmlspecialchars($socialValue) ?>
                                                </a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>
</body>

</html>