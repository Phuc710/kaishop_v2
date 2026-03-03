$webhookUrl = "http://localhost/kaishop_v2/api/sepay/webhook"
$sepayApiKey = "sepay_MaiYeuEm_2026_a8f3d9c2e1b4f7a6"

$now = Get-Date
$sepayId = Get-Random -Minimum 1000000 -Maximum 9999999
$refCode = "TEST-TF-" + $now.ToString("yyyyMMddHHmmss")

$payload = @{
    id              = $sepayId
    transferType    = "in"
    transferAmount  = 50000
    content         = "kai6V4YGQGB"
    gateway         = "MB Bank"
    accountNumber   = "09696969690"
    referenceCode   = $refCode
    transactionDate = $now.ToString("yyyy-MM-dd HH:mm:ss")
} | ConvertTo-Json -Depth 5

$headers = @{
    "Content-Type"  = "application/json"
    "Authorization" = "Apikey $sepayApiKey"
}

Invoke-WebRequest -Uri $webhookUrl -Method Post -Headers $headers -Body $payload | Select-Object -ExpandProperty Content

php /home/kaishopi/domains/kaishop.id.vn/public_html/public/telegram/cron.php >> /dev/null 2>&1
https://kaishop.id.vn/api/sepay/webhook

Mở CMD hoặc PowerShell tại thư mục dự án 
cd c:\xampp\htdocs\kaishop_v2
Gõ lệnh sau:
bash
php public/telegram/cron.php --poll

CREATE TABLE IF NOT EXISTS `telegram_logs` (
  [id](cci:1://file:///c:/xampp/htdocs/kaishop_v2/app/Helpers/NavConfig.php:273:4-325:5) BIGINT AUTO_INCREMENT PRIMARY KEY,
  `level` ENUM('INFO','WARN','ERROR') NOT NULL DEFAULT 'INFO',
  `type` ENUM('INCOMING','OUTGOING') NOT NULL DEFAULT 'INCOMING',
  `category` VARCHAR(50) NOT NULL DEFAULT 'GENERAL',
  `message` TEXT NOT NULL,
  `data` LONGTEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_tl_level_created` (`level`, `created_at`),
  KEY `idx_tl_type_created` (`type`, `created_at`),
  KEY `idx_tl_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
