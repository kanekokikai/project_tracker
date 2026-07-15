<?php
/**
 * 本番初回のみ: マイグレーション実行用。
 * 使い方:
 *   1. 本番 .env に APP_SETUP_TOKEN=長い乱数 を入れる
 *   2. ブラウザで https://ドメイン/server-setup.php?token=その値 を開く
 *   3. 成功したら必ずこのファイルをサーバから削除する（または APP_SETUP_TOKEN を空にする）
 */

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;

$token = $_GET['token'] ?? '';

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$expected = (string) env('APP_SETUP_TOKEN', '');

header('Content-Type: text/plain; charset=UTF-8');

if ($expected === '' || ! hash_equals($expected, (string) $token)) {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

$exitCode = $kernel->call('migrate', ['--force' => true]);
echo $kernel->output();
echo "\nexit_code={$exitCode}\n";

if ($exitCode === 0) {
    echo "OK. Delete public/server-setup.php and clear APP_SETUP_TOKEN now.\n";
}
