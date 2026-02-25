<!DOCTYPE html>
<html lang="vi">

<head>
    <?php
    $GLOBALS['pageAssets'] = array_merge($GLOBALS['pageAssets'] ?? [], [
        'interactive_bundle' => false,
    ]);
    require __DIR__ . '/../../hethong/head2.php';
    ?>
    <title>Nạp Tiền Ngân Hàng | <?= htmlspecialchars((string) ($chungapi['ten_web'] ?? 'KaiShop'), ENT_QUOTES, 'UTF-8'); ?></title>
</head>

<body>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>

    <main class="bg-light">
        <section class="py-5">
            <div class="container user-page-container">
                <div class="row">
                    <div class="col-lg-3 col-md-4 mb-4">
                        <?php $activePage = $activePage ?? 'deposit'; require __DIR__ . '/../../hethong/user_sidebar.php'; ?>
                    </div>

                    <div class="col-lg-9 col-md-8">
                        <?php
                        $depositPanelCardId = 'deposit-bank-card';
                        $depositReturnUrl = url('deposit-bank');
                        require __DIR__ . '/../profile/partials/deposit_bank_panel.php';
                        ?>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/../../hethong/foot.php'; ?>
    <script src="<?= asset('assets/js/deposit-bank.js') ?>"></script>
</body>

</html>

