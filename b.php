<?php
/**
 * index.php — Discord Webhook Sender (1 file)
 * - Mở trang sẽ có form nhập nội dung + bấm GỬI.
 * - Dán webhook mới vào $DISCORD_WEBHOOK_URL.
 */

declare(strict_types=1);

$DISCORD_WEBHOOK_URL = 'https://discord.com/api/webhooks/1477666159359955146/iLOAqWL-4uznX24eq7yY_jXxS-tDWUqm0mhVdUSIEjtqQ2HhCcOZgec8-dNgpBZpxPG2'; // <-- dán webhook MỚI (đã regenerate)
$DEFAULT_TITLE = 'Kai Notify';
$DEFAULT_NAME  = 'KaiShop Bot';

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function sendDiscordWebhook(string $url, array $payload): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $res, $err];
}

$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string)($_POST['title'] ?? $DEFAULT_TITLE));
    $msg   = trim((string)($_POST['message'] ?? ''));

    if ($msg === '') {
        $alert = ['type' => 'error', 'text' => 'Vui lòng nhập nội dung.'];
    } elseif ($DISCORD_WEBHOOK_URL === 'PASTE_NEW_WEBHOOK_HERE') {
        $alert = ['type' => 'error', 'text' => 'Chưa dán webhook mới vào file index.php'];
    } else {
        $payload = [
            'username' => $DEFAULT_NAME,
            'content'  => null,
            'embeds'   => [[
                'title' => $title,
                'description' => $msg,
                'timestamp' => gmdate('c'),
                'footer' => ['text' => 'KaiShop System'],
            ]]
        ];

        [$code, $res, $err] = sendDiscordWebhook($DISCORD_WEBHOOK_URL, $payload);

        if ($err) {
            $alert = ['type' => 'error', 'text' => "cURL error: {$err}"];
        } elseif ($code === 204 || ($code >= 200 && $code < 300)) {
            $alert = ['type' => 'ok', 'text' => "✅ Đã gửi thành công (HTTP {$code})"];
        } else {
            $alert = ['type' => 'error', 'text' => "❌ Gửi thất bại (HTTP {$code}). Response: {$res}"];
        }
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Kai Discord Webhook Test</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;max-width:820px;margin:40px auto;padding:0 16px}
    .card{border:1px solid #ddd;border-radius:14px;padding:16px}
    label{display:block;margin:12px 0 6px;font-weight:700}
    input,textarea{width:100%;padding:10px 12px;border:1px solid #ccc;border-radius:12px;font-size:14px}
    textarea{min-height:160px;resize:vertical}
    button{padding:10px 14px;border:0;border-radius:12px;cursor:pointer;font-weight:800;background:#111;color:#fff}
    .alert{margin:12px 0;padding:10px 12px;border-radius:12px}
    .ok{background:#e8fff1;border:1px solid #9be7b5}
    .error{background:#fff0f0;border:1px solid #ffb3b3}
    .hint{color:#666;font-size:13px;margin-top:10px;line-height:1.5}
    code{background:#f5f5f5;padding:2px 6px;border-radius:8px}
  </style>
</head>
<body>

<h2>Kai Discord Notify (Webhook)</h2>

<div class="card">
  <?php if ($alert): ?>
    <div class="alert <?= $alert['type']==='ok' ? 'ok' : 'error' ?>">
      <?= h($alert['text']) ?>
    </div>
  <?php endif; ?>

  <form method="post">
    <label>Tiêu đề</label>
    <input name="title" value="<?= h($_POST['title'] ?? $DEFAULT_TITLE) ?>" placeholder="VD: Nạp tiền thành công">

    <label>Nội dung thông báo</label>
    <textarea name="message" placeholder="VD: User #1024 vừa nạp 150.000đ"><?= h($_POST['message'] ?? '') ?></textarea>

    <div style="margin-top:14px">
      <button type="submit">🚀 GỬI</button>
    </div>

    <div class="hint">
      Nếu HTTP <code>204</code> là gửi OK (Discord webhook thường trả 204 No Content). :contentReference[oaicite:2]{index=2}<br>
      Nhớ: webhook là “secret URL”, lộ là phải xoá/regenerate ngay. :contentReference[oaicite:3]{index=3}
    </div>
  </form>
</div>

</body>
</html>