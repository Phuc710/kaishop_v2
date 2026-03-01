param(
    [string]$BaseUrl = "http://localhost/kaishop_v2"
)

$ErrorActionPreference = 'Stop'

function Get-EnvValue {
    param(
        [string]$FilePath,
        [string]$Key
    )

    $line = Select-String -Path $FilePath -Pattern ("^" + [regex]::Escape($Key) + "=(.*)$") | Select-Object -First 1
    if (-not $line) {
        throw "Missing key '$Key' in $FilePath"
    }

    return $line.Matches[0].Groups[1].Value.Trim()
}

function Get-WebhookSegment {
    param([string]$ConfigPath)

    $line = Select-String -Path $ConfigPath -Pattern "WEBHOOK_PATH_SEGMENT\s*=\s*'([^']+)'" | Select-Object -First 1
    if (-not $line) {
        throw "Cannot find WEBHOOK_PATH_SEGMENT in $ConfigPath"
    }

    return $line.Matches[0].Groups[1].Value.Trim()
}

function Invoke-Webhook {
    param(
        [string]$Uri,
        [string]$Token,
        [string]$Payload
    )

    $headers = @{
        'X-Telegram-Bot-Api-Secret-Token' = $Token
        'Content-Type'                    = 'application/json'
    }

    try {
        $response = Invoke-WebRequest -Uri $Uri -Method Post -Headers $headers -Body $Payload -TimeoutSec 20
        return [pscustomobject]@{
            StatusCode = [int]$response.StatusCode
            Content    = [string]$response.Content
        }
    } catch {
        $response = $null
        if ($_.Exception -and $_.Exception.Response) {
            $response = $_.Exception.Response
        }

        if (-not $response) {
            throw
        }

        $statusCode = [int]$response.StatusCode
        $content = ''

        try {
            $stream = $response.GetResponseStream()
            if ($stream) {
                $reader = New-Object System.IO.StreamReader($stream)
                $content = $reader.ReadToEnd()
                $reader.Dispose()
                $stream.Dispose()
            }
        } catch {
            $content = ''
        }

        return [pscustomobject]@{
            StatusCode = $statusCode
            Content    = [string]$content
        }
    }
}

$root = Split-Path -Parent $PSScriptRoot
$envPath = Join-Path $root '.env'
$configPath = Join-Path $root 'app/Services/TelegramConfig.php'

$secret = Get-EnvValue -FilePath $envPath -Key 'TELEGRAM_WEBHOOK_SECRET'
$segment = Get-WebhookSegment -ConfigPath $configPath
$base = $BaseUrl.TrimEnd('/')
$candidates = @(
    ($base + '/api/' + $segment + '/index.php'),
    ($base + '/public/api/' + $segment + '/index.php')
)

$endpoint = $null
foreach ($candidate in $candidates) {
    $probe = Invoke-Webhook -Uri $candidate -Token 'probe_token' -Payload '{"update_id":1}'
    if ($probe.StatusCode -ne 404) {
        $endpoint = $candidate
        break
    }
}

if (-not $endpoint) {
    throw "Cannot resolve webhook endpoint. Tried: $($candidates -join ', ')"
}

$payload = '{"update_id":987654321,"message":{"message_id":1,"date":1700000000,"from":{"id":123456789,"is_bot":false,"first_name":"WebhookTest"},"chat":{"id":123456789,"type":"private"},"text":"/start"}}'

Write-Host "Testing endpoint: $endpoint"

$invalid = Invoke-Webhook -Uri $endpoint -Token 'invalid_secret_for_test' -Payload $payload
if ($invalid.StatusCode -ne 403) {
    throw "Expected 403 for invalid token, got $($invalid.StatusCode). Body: $($invalid.Content)"
}
if ($invalid.Content -match '<html|<body|<!doctype') {
    throw "Invalid-token response should not be HTML. Body: $($invalid.Content)"
}
Write-Host "[PASS] Invalid token returns 403"

$valid = Invoke-Webhook -Uri $endpoint -Token $secret -Payload $payload
if ($valid.StatusCode -ne 200) {
    throw "Expected 200 for valid token, got $($valid.StatusCode). Body: $($valid.Content)"
}
if ($valid.Content -notmatch '"ok"\s*:\s*true') {
    throw "Valid-token response body should contain ok:true. Body: $($valid.Content)"
}
if ($valid.Content -match '<html|<body|<!doctype') {
    throw "Valid-token response looks like HTML (possible AntiFlood redirect). Body: $($valid.Content)"
}
Write-Host "[PASS] Valid token accepted and response is JSON"

Write-Host "All Telegram webhook hardening checks passed."
