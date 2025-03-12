<?php
// 環境の切り替え（database.phpと同じ変数を使用）
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php'; // 追加: 認証ファイルの読み込み

// 認証状態を確認 - 追加
$isAuth = isAuthenticated();

// 環境に応じたベースパスを設定
$basePath = ($environment === 'local') ? '/project_tracker' : '/project-tracker';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>プロジェクト管理</title>
    <!-- 強制的にキャッシュをクリアするためのバージョン番号を追加 -->
    <link rel="stylesheet" href="<?php echo $basePath; ?>/css/style.css?v=<?php echo time(); ?>">
</head>

<style type="text/css">
/* 緊急修正：入力フィールドの幅を調整 */
input[type="text"], 
input[type="password"], /* 追加: パスワード入力フィールド用 */
textarea, 
select {
  width: 100% !important;
  padding: 0.8rem !important;
  font-size: 1rem !important;
  box-sizing: border-box !important;
}

textarea {
  min-height: 150px !important;
  resize: vertical !important;
}

.form-group {
  margin-bottom: 1.2rem !important;
}

.modal-content {
  padding: 1.5rem !important;
}

/* 特定のセレクタを厳密に指定 */
#progressAuthor, #progressContent,
#projectName, #subProjectName,
#statusAuthor, #newStatus {
  width: 100% !important;
  box-sizing: border-box !important;
}

/* 追加: 認証関連のスタイル */
.auth-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(255, 255, 255, 0.5);
  z-index: 900;
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  transition: all 0.5s ease-in-out;
}

.auth-overlay.authenticated {
  backdrop-filter: blur(0);
  -webkit-backdrop-filter: blur(0);
  pointer-events: none;
  opacity: 0;
}

#authModal {
  z-index: 1001;
  display: flex;
  align-items: center;
  justify-content: center;
}

#authModal .modal-content {
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
  max-width: 400px;
  border-radius: 12px;
}

#authModal h2 {
  margin-top: 0;
  color: var(--apple-primary);
}

.blur-content {
  filter: blur(0);
  transition: filter 0.5s ease-in-out;
}

/* ログアウトリンク用スタイル */
.logout-link {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--apple-primary);
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 500;
  /* 常に表示する（条件付き表示を削除） */
  display: inline-block;
}

.logout-link:hover {
  text-decoration: underline;
}
</style>

<body>
    <header class="header">
        <div class="header-content">
            <h1>プロジェクト管理</h1>
            <!-- 常にログアウトリンクを表示する（条件判定を削除） -->
            <a href="logout.php" class="logout-link">ログアウト</a>
        </div>
    </header>

    <!-- 追加: 認証オーバーレイ -->
    <div class="auth-overlay <?php echo $isAuth ? 'authenticated' : ''; ?>"></div>
    
    <!-- 追加: 認証モーダル -->
    <div id="authModal" class="modal" style="display: <?php echo $isAuth ? 'none' : 'flex'; ?>;">
        <div class="modal-content">
            <h2>プロジェクト管理システム</h2>
            <p>このシステムはパスワードで保護されています。</p>
            <form id="authForm">
                <div class="form-group">
                    <label for="password">パスワード</label>
                    <input type="password" id="password" name="password" class="form-control" required autofocus>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">ログイン</button>
                </div>
                <div id="authMessage" class="auth-message" style="color: red; margin-top: 10px;"></div>
            </form>
        </div>
    </div>
    
    <main class="main-content <?php echo !$isAuth ? 'blur-content' : ''; ?>">