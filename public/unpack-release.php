<?php
/**
 * GitHub Actions が上げた release.zip を展開する。
 * URL: /unpack-release.php?token=（unpack.token と同じ値）
 *
 * 配置場所:
 * - アプリ直下（初回・旧PHP置き換え直後向け）
 * - public/ 配下（Laravel 公開後向け）
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=UTF-8');

$token = (string) ($_GET['token'] ?? '');

// public/ 配下なら親、アプリ直下ならここがルート
$root = is_dir(__DIR__ . DIRECTORY_SEPARATOR . 'app')
    ? __DIR__
    : dirname(__DIR__);

$tokenFile = $root . DIRECTORY_SEPARATOR . 'unpack.token';
$zipPath = $root . DIRECTORY_SEPARATOR . 'release.zip';

$expected = is_file($tokenFile) ? trim((string) file_get_contents($tokenFile)) : '';

if ($expected === '' || $token === '' || ! hash_equals($expected, $token)) {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

if (! class_exists('ZipArchive')) {
    http_response_code(500);
    echo "ZipArchive extension is not available.\n";
    exit;
}

if (! is_file($zipPath)) {
    http_response_code(404);
    echo "release.zip not found at {$zipPath}\n";
    exit;
}

$zip = new ZipArchive();
$opened = $zip->open($zipPath);

if ($opened !== true) {
    http_response_code(500);
    echo "Failed to open release.zip (code={$opened})\n";
    exit;
}

if (! $zip->extractTo($root)) {
    $zip->close();
    http_response_code(500);
    echo "Failed to extract release.zip\n";
    exit;
}

$zip->close();

@unlink($zipPath);
@unlink($tokenFile);

foreach ([
    $root . '/storage/framework/cache',
    $root . '/storage/framework/sessions',
    $root . '/storage/framework/views',
    $root . '/storage/logs',
    $root . '/bootstrap/cache',
] as $dir) {
    if (! is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
}

echo "OK extracted to {$root}\n";
echo "Next: /server-setup.php?token=APP_SETUP_TOKEN if needed\n";

@unlink(__FILE__);
