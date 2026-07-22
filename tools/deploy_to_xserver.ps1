param(
    [switch]$Full
)

$ErrorActionPreference = "Stop"

$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root

Write-Host ""
Write-Host "========================================"
if ($Full) {
    Write-Host " Full deploy (zip + vendor) to Xserver"
} else {
    Write-Host " Fast deploy (changed app files only)"
}
Write-Host "========================================"
Write-Host ""

$configPath = Join-Path $Root "deploy.config.bat"
if (-not (Test-Path $configPath)) {
    Write-Host "Missing deploy.config.bat"
    exit 1
}

$secretEnv = Join-Path $Root "tools\PRODUCTION_ENV.secret.txt"
if (-not (Test-Path $secretEnv)) {
    Write-Host "Missing tools\PRODUCTION_ENV.secret.txt"
    Write-Host "Run tools\prepare_production_env.bat first."
    exit 1
}

$FTP_HOST = $null; $FTP_USER = $null; $FTP_PASS = $null; $REMOTE_DIR = $null; $FTP_PROTOCOL = "ftpes"
Get-Content $configPath -Encoding ASCII | ForEach-Object {
    $line = $_.Trim()
    if ($line -match '^set\s+FTP_HOST=(.+)$') { $script:FTP_HOST = $Matches[1].Trim() }
    if ($line -match '^set\s+FTP_USER=(.+)$') { $script:FTP_USER = $Matches[1].Trim() }
    if ($line -match '^set\s+FTP_PASS=(.+)$') { $script:FTP_PASS = $Matches[1].Trim() }
    if ($line -match '^set\s+REMOTE_DIR=(.+)$') { $script:REMOTE_DIR = $Matches[1].Trim() }
    if ($line -match '^set\s+FTP_PROTOCOL=(.+)$') { $script:FTP_PROTOCOL = $Matches[1].Trim().ToLower() }
}
if ([string]::IsNullOrWhiteSpace($FTP_HOST) -or [string]::IsNullOrWhiteSpace($FTP_USER) -or [string]::IsNullOrWhiteSpace($FTP_PASS)) {
    Write-Host "Fix FTP settings in deploy.config.bat"
    exit 1
}
if ([string]::IsNullOrWhiteSpace($REMOTE_DIR)) { $REMOTE_DIR = "/" }

$candidates = @(
    (Join-Path $env:LOCALAPPDATA "Programs\WinSCP\WinSCP.com"),
    (Join-Path ${env:ProgramFiles} "WinSCP\WinSCP.com"),
    (Join-Path ${env:ProgramFiles(x86)} "WinSCP\WinSCP.com"),
    (Join-Path $PSScriptRoot "winscp\WinSCP.com")
)
$winscpCom = $candidates | Where-Object { Test-Path $_ } | Select-Object -First 1
if (-not $winscpCom) {
    Write-Host "WinSCP.com not found."
    exit 1
}

$setupToken = ""
Get-Content $secretEnv -Encoding UTF8 | ForEach-Object {
    if ($_ -match '^APP_SETUP_TOKEN=(.*)$') {
        $setupToken = $Matches[1].Trim().Trim('"')
    }
}

if ([string]::IsNullOrWhiteSpace($setupToken)) {
    Write-Host "APP_SETUP_TOKEN missing in tools\PRODUCTION_ENV.secret.txt"
    exit 1
}

function Invoke-ServerMigrate {
    param([string]$Token)

    Write-Host ""
    Write-Host "Running database migrations on server..."
    $migrated = $false

    foreach ($i in 1..5) {
        foreach ($path in @("server-setup.php", "public/server-setup.php")) {
            try {
                $url = "https://project.kanekokikai-app.com/$path" + "?token=$Token"
                $resp = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 180
                Write-Host "attempt=$i path=$path status=$($resp.StatusCode)"
                Write-Host $resp.Content

                if ($resp.StatusCode -eq 200 -and ($resp.Content -match 'exit_code=0' -or $resp.Content -match '(?m)^OK')) {
                    $migrated = $true
                    break
                }
            } catch {
                Write-Host "attempt=$i path=$path error=$($_.Exception.Message)"
            }
        }

        if ($migrated) { break }
        Start-Sleep -Seconds 3
    }

    if (-not $migrated) {
        Write-Host "Migration failed. Open server-setup.php manually if needed."
        exit 1
    }

    Write-Host "Migration OK."
}

$userEnc = [uri]::EscapeDataString($FTP_USER)
$passEnc = [uri]::EscapeDataString($FTP_PASS)
$sessionUrl = "{0}://{1}:{2}@{3}/" -f $FTP_PROTOCOL, $userEnc, $passEnc, $FTP_HOST
$scriptFile = Join-Path $env:TEMP ("pt_laravel_deploy_" + [guid]::NewGuid().ToString("N") + ".txt")
$logFile = Join-Path $env:TEMP ("pt_laravel_deploy_" + [guid]::NewGuid().ToString("N") + ".log")
$q = [char]34
if ($REMOTE_DIR -eq '/' -or $REMOTE_DIR -eq './') { $cdLine = "cd /" } else { $cdLine = "cd " + $q + $REMOTE_DIR.TrimEnd('/') + $q }

# Preserve local .env before swapping in production secrets for upload.
$envPath = Join-Path $Root ".env"
$localEnvBackup = Join-Path $env:TEMP ("pt_local_env_" + [guid]::NewGuid().ToString("N") + ".env")
$shouldRestoreLocalEnv = $false
if (Test-Path $envPath) {
    $currentEnv = Get-Content $envPath -Raw -ErrorAction SilentlyContinue
    if ($currentEnv -match '(?m)^APP_ENV=local\b') {
        Copy-Item $envPath $localEnvBackup -Force
        $shouldRestoreLocalEnv = $true
    }
}

Copy-Item $secretEnv $envPath -Force

try {
    if ($Full) {
        if (-not (Test-Path (Join-Path $Root "vendor\autoload.php"))) {
            Write-Host "Running composer install --no-dev ..."
            composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
            if ($LASTEXITCODE -ne 0) { exit 1 }
        }

        Write-Host "Building release.zip (slow full path)..."
        php (Join-Path $PSScriptRoot "build_release_zip.php")
        if ($LASTEXITCODE -ne 0) { exit 1 }

        $setupToken = (Get-Content (Join-Path $Root "unpack.token") -Encoding ASCII | Select-Object -First 1).Trim()

        $deployFtp = Join-Path $Root "deploy-ftp"
        if (Test-Path $deployFtp) { Remove-Item $deployFtp -Recurse -Force }
        New-Item -ItemType Directory -Path (Join-Path $deployFtp "public") | Out-Null
        Copy-Item (Join-Path $Root "release.zip") (Join-Path $deployFtp "release.zip")
        Copy-Item (Join-Path $Root "unpack.token") (Join-Path $deployFtp "unpack.token")
        Copy-Item (Join-Path $Root "public\unpack-release.php") (Join-Path $deployFtp "unpack-release.php")
        Copy-Item (Join-Path $Root "public\unpack-release.php") (Join-Path $deployFtp "public\unpack-release.php")
        Copy-Item (Join-Path $Root "public\fix-cache.php") (Join-Path $deployFtp "fix-cache.php")
        Copy-Item (Join-Path $Root "public\fix-cache.php") (Join-Path $deployFtp "public\fix-cache.php")
        Copy-Item (Join-Path $Root "public\server-setup.php") (Join-Path $deployFtp "public\server-setup.php")

        $lines = @(
            "option batch continue",
            "option confirm off",
            "option transfer binary",
            "open $sessionUrl",
            ("lcd " + $q + $deployFtp + $q),
            $cdLine,
            "put -resume release.zip",
            "put -resume unpack.token",
            "put -resume unpack-release.php",
            "put -resume fix-cache.php",
            "mkdir public",
            ("lcd " + $q + (Join-Path $deployFtp "public") + $q),
            "cd public",
            "put -resume unpack-release.php",
            "put -resume fix-cache.php",
            "put -resume server-setup.php",
            "exit"
        )
    } else {
        $exclude = "|vendor/;node_modules/;.git/;.github/;tests/;tools/;deploy-ftp/;public/uploads/;storage/logs/;storage/framework/cache/;storage/framework/sessions/;storage/framework/views/;storage/pail/;*.md;release.zip;unpack.token;phpunit.xml;vite.config.js;package.json;package-lock.json;.env.example;.env.production.example;.env.backup;.env.production;.env.production.release;deploy.config.bat;deploy.config.example.bat;release_via_github.bat;deploy_to_xserver.bat;"

        Write-Host "Syncing changed app files only (skip vendor)..."
        $lines = @(
            "option batch continue",
            "option confirm off",
            "option transfer binary",
            "open $sessionUrl",
            ("lcd " + $q + $Root + $q),
            $cdLine,
            ("synchronize remote -filemask=" + $q + $exclude + $q),
            "put -resume .env",
            "put -resume .htaccess",
            "put -resume index.php",
            "put -resume artisan",
            "put -resume composer.json",
            "put -resume public/server-setup.php",
            "exit"
        )
    }

    Set-Content -Path $scriptFile -Value $lines -Encoding ASCII

    Write-Host "Uploading via WinSCP..."
    Write-Host "Host: $FTP_HOST"
    & $winscpCom "/script=$scriptFile" "/log=$logFile" "/ini=nul"
    $code = $LASTEXITCODE
    Remove-Item $scriptFile -Force -ErrorAction SilentlyContinue
    if ($code -ne 0) {
        Write-Host "WinSCP failed. ExitCode=$code"
        Write-Host "Log: $logFile"
        exit $code
    }

    if ($Full) {
        Write-Host "Unpacking on server..."
        $ok = $false
        foreach ($i in 1..8) {
            foreach ($path in @("unpack-release.php", "public/unpack-release.php")) {
                try {
                    $url = "https://project.kanekokikai-app.com/$path" + "?token=$setupToken"
                    $resp = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 180
                    Write-Host "attempt=$i path=$path status=$($resp.StatusCode)"
                    Write-Host $resp.Content
                    if ($resp.StatusCode -eq 200 -and $resp.Content -match '^OK') {
                        $ok = $true
                        break
                    }
                } catch {
                    Write-Host "attempt=$i path=$path error=$($_.Exception.Message)"
                }
            }
            if ($ok) { break }
            Start-Sleep -Seconds 3
        }
        if (-not $ok) {
            Write-Host "Unpack failed."
            exit 1
        }

        foreach ($path in @("fix-cache.php", "public/fix-cache.php")) {
            try {
                $url = "https://project.kanekokikai-app.com/$path" + "?token=$setupToken"
                $resp = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 60
                Write-Host $resp.Content
                if ($resp.Content -match '^done') { break }
            } catch {}
        }
    }

    Invoke-ServerMigrate -Token $setupToken

    Write-Host ""
    Write-Host "Deploy finished."
    Write-Host "Site: https://project.kanekokikai-app.com/"
    if (-not $Full) {
        Write-Host "Tip: vendor update only -> powershell -File tools\deploy_to_xserver.ps1 -Full"
    }
    Write-Host ""
} finally {
    if ($shouldRestoreLocalEnv -and (Test-Path $localEnvBackup)) {
        Copy-Item $localEnvBackup $envPath -Force
        Write-Host "Restored local .env"
    }
    Remove-Item $localEnvBackup -Force -ErrorAction SilentlyContinue
}

exit 0
