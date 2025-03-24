<?php
require_once __DIR__ . '/includes/auth.php';

// ユーザーをログアウト
logoutUser();

// ログインページにリダイレクト
header('Location: index.php');
exit;