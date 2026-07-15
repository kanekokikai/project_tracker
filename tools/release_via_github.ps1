$ErrorActionPreference = "Stop"
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

Set-Location (Split-Path -Parent $PSScriptRoot)

Write-Host ""
Write-Host "========================================"
Write-Host " GitHub release (push -> Actions -> Xserver)"
Write-Host " Laravel: project_tracker_v2"
Write-Host "========================================"
Write-Host ""

git rev-parse --is-inside-work-tree 2>$null | Out-Null
if ($LASTEXITCODE -ne 0) {
    Write-Host "Not a git repository. Run: git init"
    exit 1
}

$remote = git remote get-url origin 2>$null
if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($remote)) {
    Write-Host "origin が未設定です。"
    Write-Host "例: git remote add origin https://github.com/kanekokikai/project_tracker.git"
    exit 1
}

Write-Host "Remote: $remote"
Write-Host ""
Write-Host "--- git status ---"
git status -sb
Write-Host ""

$status = git status --porcelain
$branch = (git rev-parse --abbrev-ref HEAD).Trim()

if ([string]::IsNullOrWhiteSpace($status)) {
    Write-Host "No local changes."
    Write-Host "Push current branch to trigger Actions? (y/N)"
    $ans = Read-Host
    if ($ans -ne "y" -and $ans -ne "Y") {
        Write-Host "Actions: https://github.com/kanekokikai/project_tracker/actions"
        exit 0
    }
} else {
    Write-Host "Excluded automatically:"
    Write-Host "  - .env / .env.backup"
    Write-Host "  - public/uploads"
    Write-Host "  - vendor / node_modules (CIで構築)"
    Write-Host ""

    $msg = Read-Host "Commit message"
    if ([string]::IsNullOrWhiteSpace($msg)) {
        Write-Host "Empty message. Aborted."
        exit 1
    }

    Write-Host ""
    Write-Host "Staging..."
    git add -A
    git reset -- .env 2>$null | Out-Null
    git reset -- .env.backup 2>$null | Out-Null
    git reset -- public/uploads/project_files 2>$null | Out-Null

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
}

if ($branch -ne "main") {
    Write-Host ""
    Write-Host "Current branch is '$branch' (Actions deploys only main)."
    Write-Host "Continue push to '$branch'? (y/N)"
    $ok = Read-Host
    if ($ok -ne "y" -and $ok -ne "Y") {
        exit 1
    }
}

Write-Host "Push to GitHub..."
git push -u origin HEAD
if ($LASTEXITCODE -ne 0) {
    Write-Host "Push failed."
    Write-Host "初回で履歴が衝突する場合は RELEASE.md の『初回Git接続』を確認してください。"
    exit 1
}

Write-Host ""
Write-Host "========================================"
Write-Host " Push OK. GitHub Actions will deploy."
Write-Host "========================================"
Write-Host "Actions: https://github.com/kanekokikai/project_tracker/actions"
Write-Host "Site:    https://project.kanekokikai-app.com/"
Write-Host ""
Write-Host "初回のみ: RELEASE.md のサーバ作業（PHP 8.2 / uploads移動 / migrate）を実施。"
Write-Host ""

Start-Process "https://github.com/kanekokikai/project_tracker/actions"
exit 0
