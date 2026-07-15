<?php
/**
 * 一時診断用。確認後に削除してください。
 * /diag.php?token=APP_SETUP_TOKEN
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=UTF-8');
ini_set('display_errors', '1');
error_reporting(E_ALL);

$token = (string) ($_GET['token'] ?? '');
$root = is_dir(__DIR__ . '/app') ? __DIR__ : dirname(__DIR__);
$envFile = $root . '/.env';

$expected = '';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line);
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

echo "php=" . PHP_VERSION . "\n";
echo "root={$root}\n";
echo "env_exists=" . (is_file($envFile) ? 'yes' : 'no') . "\n";
echo "vendor=" . (is_file($root . '/vendor/autoload.php') ? 'yes' : 'no') . "\n";
echo "storage_writable=" . (is_writable($root . '/storage') ? 'yes' : 'no') . "\n";
echo "bootstrap_cache_writable=" . (is_writable($root . '/bootstrap/cache') ? 'yes' : 'no') . "\n";

foreach ([
    'storage',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache',
] as $rel) {
    $path = $root . '/' . $rel;
    if (! is_dir($path)) {
        @mkdir($path, 0777, true);
    }
    @chmod($path, 0777);
    echo "path {$rel} exists=" . (is_dir($path) ? 'yes' : 'no') . " writable=" . (is_writable($path) ? 'yes' : 'no') . "\n";
}

try {
    require $root . '/vendor/autoload.php';
    $app = require $root . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "laravel_boot=ok\n";
    echo "app_env=" . $app->environment() . "\n";
} catch (Throwable $e) {
    echo "laravel_boot=FAIL\n";
    echo $e::class . ': ' . $e->getMessage() . "\n";
    echo $e->getFile() . ':' . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
