# Laravel 本番リリース手順（GitHub → Xserver）

このアプリは `release_via_github.bat` で GitHub に push すると、Actions が Composer 込みで FTP デプロイします。

本番URL: https://project.kanekokikai-app.com/

---

## いま一度だけやること（初回カットオーバー）

### 1. エックスサーバーの PHP を 8.2 以上にする

ドメイン設定 → PHP ver. 切替 → **8.2 以上**（Laravel 12 必須）

### 2. GitHub Secrets を確認・追加

既存の FTP 系はそのまま使えます。

| Secret | 内容 |
|--------|------|
| `FTP_SERVER` | 例: `sv16374.xserver.jp` |
| `FTP_USERNAME` | FTP ユーザー |
| `FTP_PASSWORD` | FTP パスワード |
| `FTP_SERVER_DIR` | 通常 `/` |
| **`PRODUCTION_ENV`（追加）** | 本番 `.env` の全文 |

`PRODUCTION_ENV` の作り方（簡単）:

1. `tools\prepare_production_env.bat` を実行する  
2. できた `tools\PRODUCTION_ENV.secret.txt` の中身をコピー  
3. GitHub → Settings → Secrets and variables → Actions → New repository secret  
   - Name: `PRODUCTION_ENV`  
   - Value: 貼り付け → Save  

（手で作る場合は `.env.production.example` を埋めてもOK）

```bat
php -r "echo 'base64:'.base64_encode(random_bytes(32)), PHP_EOL;"
```

（APP_KEY は `prepare_production_env.bat` が自動発行します）

### 3. ローカル Git を本番リポジトリに接続

`project_tracker_v2` フォルダで:

```bat
git init
git remote add origin https://github.com/kanekokikai/project_tracker.git
git fetch origin
git checkout -b main
```

すでに `origin/main` がある場合（旧PHP版）:

```bat
git pull origin main --allow-unrelated-histories
```

衝突したらこちらの Laravel 側を優先して解決し、コミットしてください。  
**このリリースで旧PHP本体は本番から置き換わります。** ローカルの `project_tracker` フォルダはロールバック用に残してください。

### 4. 添付ファイルの移動（本番ファイルマネージャ）

旧アプリの添付は `uploads/project_files/` にあります。  
Laravel では `public/uploads/project_files/` です。

初回デプロイ後、File Manager で:

- `uploads/project_files` → `public/uploads/project_files` へ **移動（またはコピー）**

（FTP はuploadsを上書き削除しません）

### 5. 書き込み権限

`storage` と `bootstrap/cache` に書き込み権限（例: 705〜777）を付ける。

### 6. 初回マイグレーション

デプロイ成功後:

1. `https://project.kanekokikai-app.com/server-setup.php?token=（APP_SETUP_TOKEN）` を開く
2. `OK` が出たら、**サーバから `public/server-setup.php` を削除**
3. GitHub の `PRODUCTION_ENV` から `APP_SETUP_TOKEN` 行を削除（または空）して次回以降デプロイ

既存の `projects` 等テーブルはそのまま使い、Laravel用の補助テーブルだけ作られます。

### 7. 動作確認

- ログイン（共有パスワード）
- プロジェクト一覧・コメント・ステータス
- 添付の表示/ダウンロード（旧ファイルも含む）
- Chatwork 通知と TO設定
- サブプロジェクト作成・メンバー追加

---

## 普段のリリース

1. `project_tracker_v2` で開発
2. `release_via_github.bat` をダブルクリック
3. コミットメッセージを入力
4. GitHubへpushされたあと、**このPCからWinSCPで本番へアップロード**されます
5. https://project.kanekokikai-app.com/ を確認

> GitHub Actions の FTP は Xserver へつながりにくいため、本番反映はローカル WinSCP 方式です。
> コード履歴は GitHub に残ります。FTP設定は `deploy.config.bat`、本番envは `tools\PRODUCTION_ENV.secret.txt` です。

---

## DocumentRoot について

今のまま（ドメイン直下にアプリ全体）でも、ルートの `.htaccess` が `public/` に転送します。

可能ならサーバ設定で公開フォルダを `.../project.kanekokikai-app.com/public` にするとより安全です（その場合ルートの `index.php` / `.htaccess` は不要になります）。

---

## ロールバック

ローカルの旧 `c:\xampp\htdocs\project_tracker` を再度 Git で push するか、旧バックアップから FTP で戻してください。
