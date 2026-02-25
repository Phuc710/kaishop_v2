<?php
/**
 * banned.php — Trang truy cập bị hạn chế
 */
require_once __DIR__ . '/hethong/config.php';
http_response_code(403);

$siteName = $chungapi['ten_web'] ?? 'KaiShop';
$sitePolicy = url('chinh-sach');

$reason = '';
if (!empty($_SESSION['banned_reason'])) {
    $reason = htmlspecialchars((string) $_SESSION['banned_reason'], ENT_QUOTES, 'UTF-8');
}
// Clear session sau khi đọc
session_destroy();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <?php require HETHONG_PATH . '/head2.php'; ?>
    <title>Truy Cập Bị Hạn Chế |
        <?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?>
    </title>
    <meta name="description"
        content="Quyền truy cập của bạn vào <?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?> đã bị hạn chế. Vui lòng liên hệ hỗ trợ để biết thêm thông tin.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="<?= $sitePolicy ?>">
    <link rel="stylesheet" href="<?= asset('assets/css/error-pages.css') ?>">
    <style>
        /* ── Banned card extras ─────────────────────── */
        .banned-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #ef4444, #b91c1c);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 24px rgba(239, 68, 68, .35);
            animation: pulse-red 2.5s ease-in-out infinite;
        }

        .banned-icon i {
            font-size: 30px;
            color: #fff;
        }

        @keyframes pulse-red {

            0%,
            100% {
                box-shadow: 0 8px 24px rgba(239, 68, 68, .35);
            }

            50% {
                box-shadow: 0 8px 36px rgba(239, 68, 68, .6);
            }
        }

        .reason-box {
            background: rgba(239, 68, 68, .07);
            border-left: 3px solid #ef4444;
            border-radius: 6px;
            padding: 14px 18px;
            margin: 18px 0 24px;
            text-align: left;
        }

        .reason-box strong {
            display: block;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .6px;
            text-transform: uppercase;
            color: #ef4444;
            margin-bottom: 6px;
        }

        .reason-box p {
            margin: 0;
            font-size: 14px;
            color: #4b5563;
            line-height: 1.6;
        }

        .action-row {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 8px;
        }
    </style>
</head>

<body class="error-page-wrapper">
    <div class="error-card" style="max-width:480px;">

        <div class="banned-icon">
            <i class="fa-solid fa-shield-halved"></i>
        </div>

        <h1 class="error-title" style="font-size:22px;margin-bottom:8px;">
            Truy Cập Bị Hạn Chế
        </h1>

        <p class="error-message" style="margin-bottom:4px;">
            Quyền truy cập của bạn vào <strong>
                <?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?>
            </strong>
            đã bị hạn chế theo chính sách sử dụng dịch vụ.
        </p>

        <?php if ($reason !== ''): ?>
            <div class="reason-box">
                <strong>Lý do</strong>
                <p>
                    <?= $reason ?>
                </p>
            </div>
        <?php else: ?>
            <div class="reason-box">
                <strong>Lý do</strong>
                <p>Vui lòng liên hệ bộ phận hỗ trợ để được giải đáp.</p>
            </div>
        <?php endif; ?>

        <div class="action-row">
            <a href="<?= $sitePolicy ?>" class="error-action">
                <i class="fa-solid fa-book-open"></i> Xem chính sách
            </a>
        </div>

    </div>
</body>

</html>