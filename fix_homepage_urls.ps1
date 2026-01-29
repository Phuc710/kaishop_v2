# Fix all hardcoded URLs in index.php and other pages
$file = "c:\xampp\htdocs\web\index.php"
$content = Get-Content $file -Raw

# Replace hardcoded service links
$content = $content -replace 'href="/ma-nguon"', 'href="<?=url(''ma-nguon'')?>"'
$content = $content -replace 'href="/tao-web"', 'href="<?=url(''tao-web'')?>"'
$content = $content -replace 'href="/server-hosting"', 'href="<?=url(''server-hosting'')?>"'
$content = $content -replace 'href="/tao-logo"', 'href="<?=url(''tao-logo'')?>"'
$content = $content -replace 'href="/mua-mien"', 'href="<?=url(''mua-mien'')?>"'
$content = $content -replace 'href="/subdomain"', 'href="<?=url(''subdomain'')?>"'

Set-Content $file -Value $content -NoNewline
Write-Host "Fixed index.php"
Write-Host "Done!"
