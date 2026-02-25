<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require __DIR__ . '/../hethong/head2.php'; ?>
    <title>Trang Liên Hệ | <?= htmlspecialchars((string) ($chungapi['ten_web'] ?? 'KaiShop')) ?></title>
</head>

<body>
    <?php require __DIR__ . '/../hethong/nav.php'; ?>

    <?php
    $siteName = (string) ($chungapi['ten_web'] ?? 'KaiShop');
    $defaultTitle = 'Liên hệ ' . $siteName;
    $pageTitle = trim((string) ($chungapi['contact_page_title'] ?? '')) ?: $defaultTitle;
    $pageSubtitle = trim((string) ($chungapi['contact_page_subtitle'] ?? '')) ?: trim((string) ($chungapi['mo_ta'] ?? ''));
    $supportNote = trim((string) ($chungapi['contact_support_note'] ?? ''));

    $contactEmail = trim((string) ($chungapi['email_cf'] ?? ''));
    $contactPhone = trim((string) ($chungapi['sdt_admin'] ?? ''));
    $contactEmailLabel = trim((string) ($chungapi['contact_email_label'] ?? '')) ?: 'Email hỗ trợ';
    $contactPhoneLabel = trim((string) ($chungapi['contact_phone_label'] ?? '')) ?: 'Số điện thoại / Zalo';

    $socialItems = [
        ['label' => 'Facebook', 'value' => (string) ($chungapi['fb_admin'] ?? ''), 'icon_class' => 'fa-brands fa-facebook-f'],
        ['label' => 'Telegram', 'value' => (string) ($chungapi['tele_admin'] ?? ''), 'icon_class' => 'fa-brands fa-telegram'],
        ['label' => 'TikTok', 'value' => (string) ($chungapi['tiktok_admin'] ?? ''), 'icon_class' => 'fa-brands fa-tiktok'],
        ['label' => 'YouTube', 'value' => (string) ($chungapi['youtube_admin'] ?? ''), 'icon_class' => 'fa-brands fa-youtube'],
    ];
    ?>

    <main>
        <section class="contact-section py-4">
            <div class="contact-bottom bg-white">
                <div class="container">
                    <div class="row justify-content-center mb-4">
                        <div class="col-lg-9 text-center">
                            <h2 class="mb-2" style="font-weight:700; color:#1f2937;"><?= htmlspecialchars($pageTitle) ?></h2>
                            <?php if ($pageSubtitle !== ''): ?>
                                <p class="text-muted mb-2"><?= htmlspecialchars($pageSubtitle) ?></p>
                            <?php endif; ?>
                            <?php if ($supportNote !== ''): ?>
                                <p class="mb-0" style="color:#0f766e; font-weight:600;"><?= htmlspecialchars($supportNote) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row justify-content-center">
                        <?php if ($contactEmail !== ''): ?>
                            <div class="col-lg-4 col-md-6 d-flex mb-3">
                                <div class="contact-grid w-100">
                                    <div class="contact-content">
                                        <div class="contact-icon">
                                            <span>
                                                <img src="<?= asset('assets/images/contact-mail.svg') ?>" alt="Email">
                                            </span>
                                        </div>
                                        <div class="contact-details">
                                            <h6><?= htmlspecialchars($contactEmailLabel) ?></h6>
                                            <p style="word-break: break-word;">
                                                <a href="mailto:<?= htmlspecialchars($contactEmail) ?>"><?= htmlspecialchars($contactEmail) ?></a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($contactPhone !== ''): ?>
                            <?php $phoneHref = preg_replace('/[^0-9+]/', '', $contactPhone) ?: $contactPhone; ?>
                            <div class="col-lg-4 col-md-6 d-flex mb-3">
                                <div class="contact-grid w-100">
                                    <div class="contact-content">
                                        <div class="contact-icon">
                                            <span>
                                                <img src="<?= asset('assets/images/contact-phone.svg') ?>" alt="Phone">
                                            </span>
                                        </div>
                                        <div class="contact-details">
                                            <h6><?= htmlspecialchars($contactPhoneLabel) ?></h6>
                                            <p>
                                                <a href="tel:<?= htmlspecialchars($phoneHref) ?>"><?= htmlspecialchars($contactPhone) ?></a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($socialItems as $item): ?>
                            <?php $socialValue = trim((string) ($item['value'] ?? '')); ?>
                            <?php if ($socialValue === '') continue; ?>
                            <?php
                            $socialHref = preg_match('~^https?://~i', $socialValue) ? $socialValue : ('https://' . ltrim($socialValue, '/'));
                            ?>
                            <div class="col-lg-4 col-md-6 d-flex mb-3">
                                <div class="contact-grid w-100">
                                    <div class="contact-content">
                                        <div class="contact-icon">
                                            <span>
                                                <i class="<?= htmlspecialchars((string) ($item['icon_class'] ?? 'fa-solid fa-link')) ?>" style="font-size:20px; line-height:1;"></i>
                                            </span>
                                        </div>
                                        <div class="contact-details">
                                            <h6><?= htmlspecialchars((string) $item['label']) ?></h6>
                                            <p style="word-break: break-word;">
                                                <a href="<?= htmlspecialchars($socialHref) ?>" target="_blank" rel="noopener noreferrer">
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

    <?php require __DIR__ . '/../hethong/foot.php'; ?>
</body>

</html>
