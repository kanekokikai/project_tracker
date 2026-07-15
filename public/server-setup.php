<?php
/**
 * 本番初回のみ: マイグレーション実行用。
 * 使い方: /server-setup.php?token=（.env の APP_SETUP_TOKEN）
 * 成功後にこのファイルを削除し、APP_SETUP_TOKEN を空にしてください。
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=UTF-8');

$token = (string) ($_GET['token'] ?? '');
$root = dirname(__DIR__);
$envFile = $root . DIRECTORY_SEPARATOR . '.env';

$expected = '';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_starts_with($line, 'APP_SETUP_TOKEN=')) {
            $expected = trim(substr($line, strlen('APP_SETUP_TOKEN=')), " \t\"'");
            break;
        }
    }
}

if ($expected === '' || $token === '' || ! hash_equals($expected, $token)) {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

require $root . '/vendor/autoload.php';

$app = require $root . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$exitCode = $kernel->call('migrate', ['--force' => true]);
echo $kernel->output();
echo "\nexit_code={$exitCode}\n";

if ($exitCode === 0) {
    echo "OK. Delete public/server-setup.php and clear APP_SETUP_TOKEN now.\n";
}
