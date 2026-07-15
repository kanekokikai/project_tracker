$ErrorActionPreference = "Stop"

Set-Location (Split-Path -Parent $PSScriptRoot)

Write-Host ""
Write-Host "========================================"
Write-Host " Release: git push + local WinSCP deploy"
Write-Host "========================================"
Write-Host ""
Write-Host "NOTE: GitHub Actions FTP often times out from CI."
Write-Host "This script pushes code to GitHub, then deploys from your PC."
Write-Host ""

git rev-parse --is-inside-work-tree 2>$null | Out-Null
if ($LASTEXITCODE -ne 0) {
    Write-Host "Not a git repository."
    exit 1
}

$remote = git remote get-url origin 2>$null
if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($remote)) {
    Write-Host "origin remote is missing."
    exit 1
}

Write-Host "Remote: $remote"
Write-Host ""
Write-Host "--- git status ---"
git status -sb
Write-Host ""

$status = git status --porcelain
$branch = (git rev-parse --abbrev-ref HEAD).Trim()

if (-not [string]::IsNullOrWhiteSpace($status)) {
    Write-Host "Excluded automatically:"
    Write-Host "  - .env / deploy.config.bat / uploads / vendor"
    Write-Host ""

    $msg = Read-Host "Commit message"
    if ([string]::IsNullOrWhiteSpace($msg)) {
        Write-Host "Empty message. Aborted."
        exit 1
    }

    Write-Host "Staging..."
    git add -A
    git reset -- .env 2>$null | Out-Null
    git reset -- .env.backup 2>$null | Out-Null
    git reset -- deploy.config.bat 2>$null | Out-Null
    git reset -- tools/PRODUCTION_ENV.secret.txt 2>$null | Out-Null
    git reset -- public/uploads/project_files 2>$null | Out-Null
    git reset -- release.zip 2>$null | Out-Null
    git reset -- deploy-ftp 2>$null | Out-Null
    git reset -- unpack.token 2>$null | Out-Null

    $cached = git diff --cached --name-only
    if ([string]::IsNullOrWhiteSpace($cached)) {
        Write-Host "Nothing staged. Aborted."
        exit 1
    }

    Write-Host "Commit..."
    git commit -m $msg
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Commit failed."
        exit 1
    }
} else {
    Write-Host "No local code changes to commit."
}

if ($branch -ne "main") {
    Write-Host "Current branch is '$branch'."
    Write-Host "Continue? (y/N)"
    $ok = Read-Host
    if ($ok -ne "y" -and $ok -ne "Y") { exit 1 }
}

Write-Host "Push to GitHub (source backup)..."
git push -u origin HEAD
if ($LASTEXITCODE -ne 0) {
    Write-Host "Push failed."
    exit 1
}

Write-Host ""
Write-Host "Starting local WinSCP deploy..."
& powershell -NoProfile -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot "deploy_to_xserver.ps1")
if ($LASTEXITCODE -ne 0) {
    Write-Host "Local deploy failed."
    exit 1
}

Write-Host ""
Write-Host "All done."
Start-Process "https://project.kanekokikai-app.com/"
exit 0
