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




Investigating Performance Issues
I have identified several performance bottlenecks, including heavy fingerprinting, external resource dependencies, and per-request security checks. I am now drafting an optimization plan that includes postponing and caching the fingerprint, as well as refining asset loading in the head.

Files Edited
Progress Updates
1
Analyzing index.php and hethong/head2.php for loading bottlenecks

2
Reviewing assets/js/fingerprint.js and config.php for performance bottlenecks

3
Analyzing config/app.php and checking for server-side bottlenecks

4
Searching for SCSS compilation and plan for fingerprinting optimization

5
Performing a detailed search for "scss" and finalizing the performance improvement plan

6
Reviewing security services and bot detection for performance impact

7
Analyzing AuthSecurityService.php and searching for its usage in AuthController.php and config/app.php

8
Searching for additional DOMContentLoaded listeners and checking UserAgentParser.php

9
Analyzing auth-login.js and AuthService.php for potential delays

10
Drafting optimization plan for fingerprinting and asset loading

I've investigated the slow loading issue and found several "traps" (potential bottlenecks):

Fingerprinting: The KaiFingerprint.collect() script is quite heavy, especially the font detection, and it runs on every page load for logged-in users.
External Assets: Many fonts and icons are loaded from external CDNs, which can slow down the initial connection (DNS/SSL).
Security Checks: There are broad regex-based security checks running on every request.
My proposed optimization plan:

Fingerprint Caching: Cache the fingerprint in localStorage so it only calculates once and remains valid for a few hours.
Postponed Execution: Move the fingerprinting trigger from DOMContentLoaded to window.load so it doesn't block the initial page show.
Head Optimization: Reduce the number of simultaneous external connections (preconnect).
Please review the 
implementation plan
 and let me know if I should proceed.