<?php
// 環境の切り替え（database.phpと同じ変数を使用）
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php'; // 追加: 認証ファイルの読み込み

// 認証状態を確認 - 追加
$isAuth = isAuthenticated();

// 環境に応じたベースパスを設定
$basePath = '';  // デフォルトは空（ドキュメントルート直下）

// エックスサーバー環境でパスを正しく設定
if ($environment === 'xserver') {
    $basePath = '/project_tracker';
}

// ローカル環境の場合
if ($environment === 'local') {
    $basePath = '/project_tracker';
}

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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

/* 削除確認モーダルのヘッダー非表示（強制的に） */
#delete-confirm-modal .modal-header {
  display: none !important;
}

/* 本文部分の調整 */
#delete-confirm-modal .modal-body {
  padding-top: 25px !important;
  border-radius: 8px !important;
}

/* モーダル本体のスタイル調整 */
#delete-confirm-modal .modal-content {
  border-radius: 8px !important;
  padding-top: 0 !important;
}

</style>

<body>
<!-- サイドバーボタン -->
<div class="sidebar-toggle">
    <i class="fas fa-list"></i>
    <span>プロジェクト一覧</span>
</div>

<!-- サイドバー -->
<div class="sidebar">
    <div class="sidebar-header">
        <h2>プロジェクト一覧</h2>
        <i class="fas fa-thumbtack sidebar-pin" title="サイドバーを固定"></i>
    </div>
    <div class="sidebar-content">
        <ul class="project-nav">
            <!-- JavaScriptで動的に生成されるプロジェクトリスト -->
        </ul>
    </div>
</div>

<!-- サイドバーオーバーレイ -->
<div class="sidebar-overlay"></div>


    <!-- ヘッダーは1つだけ残す -->
    <header class="header">
        <div class="header-content">
            <h1>プロジェクト管理</h1>
            <a href="logout.php" class="logout-link">ログアウト</a>
        </div>
    </header>

    <!-- 既存のコンテンツ開始部分 -->
    <div class="main-content">
        <!-- 認証オーバーレイ -->
        <div class="auth-overlay <?php echo $isAuth ? 'authenticated' : ''; ?>"></div>
        


<!-- 認証モーダル -->
<div id="authModal" class="modal" style="display: <?php echo $isAuth ? 'none' : 'flex'; ?>;">
    <div class="modal-content">
        <div class="auth-header">
            <h2>ログイン</h2>
        </div>
        <div class="auth-body">
            <form id="authForm">
                <div class="form-group">
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" class="form-control" placeholder="パスワードを入力" required>
                    </div>
                </div>
                <div id="authMessage" class="auth-message"></div>
                <div class="form-group auth-buttons">
                    <button type="submit" class="btn btn-primary auth-btn">ログイン</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* 新しい認証モーダルのスタイル */
#authModal {
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1001;
}

/* グローバルモーダルスタイルの上書き */
#authModal {
    z-index: 9999;
}

#authModal .modal-content {
    max-width: 250px !important;
    width: 250px !important;
    min-width: 250px !important;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    padding: 0;
    overflow: hidden;
    background: linear-gradient(to bottom, #fafbfc, #f2f4f7);
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.auth-header {
    padding: 15px;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    text-align: center;
}

.auth-header h2 {
    margin: 0;
    color: var(--apple-dark);
    font-size: 1.2rem;
    font-weight: 600;
}

.auth-body {
    padding: 15px;
}

.input-with-icon {
    position: relative;
}

.input-with-icon i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--apple-secondary);
}

.input-with-icon input {
    padding-left: 30px !important;
    height: 40px;
    border-radius: 12px !important;
    border: 1px solid rgba(0, 0, 0, 0.1) !important;
    background-color: white !important;
    font-size: 1rem !important;
    transition: all 0.3s ease;
    width: 100%;
    box-sizing: border-box;
}

.input-with-icon input:focus {
    border-color: var(--apple-primary) !important;
    box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.25) !important;
    outline: none;
}

.auth-message {
    color: var(--apple-error);
    font-size: 0.85rem;
    margin: 10px 0;
    text-align: center;
    min-height: 20px;
}

.auth-buttons {
    margin-top: 20px;
}

.auth-btn {
    width: 100%;
    height: 40px;
    border-radius: 12px !important;
    font-size: 1rem !important;
    font-weight: 500;
    background: linear-gradient(to bottom, #127afa, #0071e3);
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
}

.auth-btn:hover {
    background: linear-gradient(to bottom, #0071e3, #005fc4);
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(0, 113, 227, 0.3);
}

.auth-overlay {
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    background-color: rgba(250, 251, 252, 0.7);
}

.auth-overlay.authenticated {
    backdrop-filter: blur(0);
    -webkit-backdrop-filter: blur(0);
    opacity: 0;
    pointer-events: none;
    transition: all 0.8s cubic-bezier(0.19, 1, 0.22, 1);
}
</style>

<main class="main-content <?php echo !$isAuth ? 'blur-content' : ''; ?>">