# プロジェクト管理（Laravel）

本番: https://project.kanekokikai-app.com/

## リリース

1. 初回だけ [RELEASE.md](RELEASE.md) の手順（Secrets / PHP 8.2 / uploads 移動 / migrate）
2. 普段は `release_via_github.bat` を実行

## ローカル

```bat
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
```

XAMPP なら `http://localhost/project_tracker_v2/public/`
