$ErrorActionPreference = "Stop"
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$outFile = Join-Path $PSScriptRoot "PRODUCTION_ENV.secret.txt"
$key = "base64:" + [Convert]::ToBase64String((1..32 | ForEach-Object { Get-Random -Maximum 256 } | ForEach-Object { [byte]$_ } | ForEach-Object { $_ } ))

# PowerShell random_bytes equivalent
$bytes = New-Object byte[] 32
[System.Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($bytes)
$key = "base64:" + [Convert]::ToBase64String($bytes)

$setupToken = -join ((48..57) + (65..90) + (97..122) | Get-Random -Count 40 | ForEach-Object { [char]$_ })

# 既存の旧アプリ / ローカル .env から取れる値は自動埋め。なければ空。
$authPassword = "kaneko0911"
$dbPassword = "kaneko0911"
$chatworkToken = "3db459267f47f409fb534a7880508870"
$chatworkRoom = "394762259"

$localEnv = Join-Path $root ".env"
if (Test-Path $localEnv) {
    Get-Content $localEnv | ForEach-Object {
        if ($_ -match '^APP_AUTH_PASSWORD=(.*)$' -and $Matches[1]) { $authPassword = $Matches[1].Trim('"') }
        if ($_ -match '^CHATWORK_API_TOKEN=(.*)$' -and $Matches[1]) { $chatworkToken = $Matches[1].Trim('"') }
        if ($_ -match '^CHATWORK_ROOM_ID=(.*)$' -and $Matches[1]) { $chatworkRoom = $Matches[1].Trim('"') }
    }
}

$content = @"
APP_NAME="プロジェクト管理"
APP_ENV=production
APP_KEY=$key
APP_DEBUG=false
APP_URL=https://project.kanekokikai-app.com

APP_AUTH_PASSWORD=$authPassword
APP_SETUP_TOKEN=$setupToken

APP_LOCALE=ja
APP_FALLBACK_LOCALE=ja
APP_FAKER_LOCALE=ja_JP

APP_MAINTENANCE_DRIVER=file

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=xs765558_projecttracker
DB_USERNAME=xs765558_kaneko
DB_PASSWORD=$dbPassword

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
CACHE_STORE=file

MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@project.kanekokikai-app.com"
MAIL_FROM_NAME="プロジェクト管理"

CHATWORK_ENABLED=true
CHATWORK_API_TOKEN=$chatworkToken
CHATWORK_ROOM_ID=$chatworkRoom
CHATWORK_PROJECT_BASE_URL=https://project.kanekokikai-app.com
"@

Set-Content -Path $outFile -Value $content -Encoding UTF8

Write-Host ""
Write-Host "Created: tools\PRODUCTION_ENV.secret.txt"
Write-Host "この内容を GitHub Secrets の PRODUCTION_ENV にそのまま貼ってください。"
Write-Host "（このファイルは gitignore 済み。コミットしないでください）"
Write-Host ""
Write-Host "初回 migrate 用トークン:"
Write-Host $setupToken
Write-Host ""
Write-Host "サーバセットアップURL例:"
Write-Host "https://project.kanekokikai-app.com/server-setup.php?token=$setupToken"
Write-Host ""
