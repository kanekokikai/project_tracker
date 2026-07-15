$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$outFile = Join-Path $PSScriptRoot "PRODUCTION_ENV.secret.txt"

$bytes = New-Object byte[] 32
[System.Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($bytes)
$key = "base64:" + [Convert]::ToBase64String($bytes)

$setupToken = -join ((48..57) + (65..90) + (97..122) | Get-Random -Count 40 | ForEach-Object { [char]$_ })

$authPassword = "kaneko0911"
$dbPassword = "kaneko0911"
$chatworkToken = "3db459267f47f409fb534a7880508870"
$chatworkRoom = "394762259"

$localEnv = Join-Path $root ".env"
if (Test-Path $localEnv) {
    Get-Content $localEnv -Encoding UTF8 | ForEach-Object {
        if ($_ -match '^APP_AUTH_PASSWORD=(.*)$' -and $Matches[1]) { $authPassword = $Matches[1].Trim('"') }
        if ($_ -match '^CHATWORK_API_TOKEN=(.*)$' -and $Matches[1]) { $chatworkToken = $Matches[1].Trim('"') }
        if ($_ -match '^CHATWORK_ROOM_ID=(.*)$' -and $Matches[1]) { $chatworkRoom = $Matches[1].Trim('"') }
    }
}

# ASCII-only APP_NAME to avoid PowerShell encoding issues in secrets
$lines = @(
    'APP_NAME="Project Tracker"'
    'APP_ENV=production'
    "APP_KEY=$key"
    'APP_DEBUG=false'
    'APP_URL=https://project.kanekokikai-app.com'
    ''
    "APP_AUTH_PASSWORD=$authPassword"
    "APP_SETUP_TOKEN=$setupToken"
    ''
    'APP_LOCALE=ja'
    'APP_FALLBACK_LOCALE=ja'
    'APP_FAKER_LOCALE=ja_JP'
    ''
    'APP_MAINTENANCE_DRIVER=file'
    ''
    'LOG_CHANNEL=stack'
    'LOG_STACK=single'
    'LOG_LEVEL=error'
    ''
    'DB_CONNECTION=mysql'
    'DB_HOST=localhost'
    'DB_PORT=3306'
    'DB_DATABASE=xs765558_projecttracker'
    'DB_USERNAME=xs765558_kaneko'
    "DB_PASSWORD=$dbPassword"
    ''
    'SESSION_DRIVER=file'
    'SESSION_LIFETIME=120'
    'SESSION_ENCRYPT=false'
    'SESSION_PATH=/'
    'SESSION_DOMAIN=null'
    ''
    'BROADCAST_CONNECTION=log'
    'FILESYSTEM_DISK=local'
    'QUEUE_CONNECTION=sync'
    'CACHE_STORE=file'
    ''
    'MAIL_MAILER=log'
    'MAIL_FROM_ADDRESS="noreply@project.kanekokikai-app.com"'
    'MAIL_FROM_NAME="Project Tracker"'
    ''
    'CHATWORK_ENABLED=true'
    "CHATWORK_API_TOKEN=$chatworkToken"
    "CHATWORK_ROOM_ID=$chatworkRoom"
    'CHATWORK_PROJECT_BASE_URL=https://project.kanekokikai-app.com'
)

$text = ($lines -join "`n") + "`n"
$utf8NoBom = New-Object System.Text.UTF8Encoding $false
[System.IO.File]::WriteAllText($outFile, $text, $utf8NoBom)

Write-Host ""
Write-Host "Created: tools\PRODUCTION_ENV.secret.txt"
Write-Host "Paste the ENTIRE file into GitHub Secret PRODUCTION_ENV"
Write-Host ""
Write-Host "APP_SETUP_TOKEN:"
Write-Host $setupToken
Write-Host ""
Write-Host "Setup URL:"
Write-Host "https://project.kanekokikai-app.com/server-setup.php?token=$setupToken"
Write-Host ""
