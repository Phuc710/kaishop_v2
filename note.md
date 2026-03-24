$webhookUrl = "http://localhost/kaishop_v2/api/sepay/webhook"
$sepayApiKey = "sepay_MaiYeuEm_2026_a8f3d9c2e1b4f7a6"

$now = Get-Date
$sepayId = Get-Random -Minimum 1000000 -Maximum 9999999
$refCode = "TEST-TF-" + $now.ToString("yyyyMMddHHmmss")

$payload = @{
    id              = $sepayId
    transferType    = "in"
    transferAmount  = 50000
    content         = "kai67Q6D3PA"
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



php /home/kaishopi/domains/kaishop.id.vn/public_html/chatgpt/cron/guard.php
php /home/kaishopi/domains/kaishop.id.vn/public_html/public/telegram/cron.php >> /dev/null 2>&1
php /home/kaishopi/domains/kaishop.id.vn/public_html/public/gpt-business/cron.php >> /dev/null 2>&1



Mở CMD hoặc PowerShell tại thư mục dự án 
cd c:\xampp\htdocs\kaishop_v2
Gõ lệnh sau:
bash
php public/telegram/cron.php --poll
 

