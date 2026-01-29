# Fix all DOCUMENT_ROOT paths in ajax folder
Get-ChildItem -Path "c:\xampp\htdocs\web\ajax" -Recurse -Filter "*.php" | ForEach-Object {
    $content = Get-Content $_.FullName -Raw
    $originalContent = $content
    
    # Replace various patterns
    $content = $content -replace "\`$_SERVER\['DOCUMENT_ROOT'\]\s*\.\s*'/hethong/config\.php'", "__DIR__ . '/../../hethong/config.php'"
    $content = $content -replace "\`$_SERVER\['DOCUMENT_ROOT'\]\.'/hethong/config\.php'", "__DIR__ . '/../../hethong/config.php'"
    $content = $content -replace "\`$_SERVER\['DOCUMENT_ROOT'\]\.'/hethong/xulythe\.php'", "__DIR__ . '/../../hethong/xulythe.php'"
    
    if ($content -ne $originalContent) {
        Set-Content $_.FullName -Value $content -NoNewline
        Write-Host "Fixed: $($_.FullName)"
    }
}
Write-Host "`nDone!"
