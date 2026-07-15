<?php
/**
 * 一時診断用。確認後に削除。
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

$log = $root . '/storage/logs/laravel.log';
echo "log_exists=" . (is_file($log) ? 'yes' : 'no') . "\n";
if (is_file($log)) {
    $lines = file($log);
    $tail = array_slice($lines, -80);
    echo "---- laravel.log (tail) ----\n";
    echo implode('', $tail);
    echo "---- end log ----\n";
}

try {
    require $root . '/vendor/autoload.php';
    $app = require $root . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $request = Illuminate\Http\Request::create('/', 'GET');
    $response = $kernel->handle($request);
    echo "home_status=" . $response->getStatusCode() . "\n";
    $content = $response->getContent();
    echo "home_body_start=\n";
    echo substr(strip_tags($content), 0, 500) . "\n";
    $kernel->terminate($request, $response);
} catch (Throwable $e) {
    echo "request_FAIL\n";
    echo $e::class . ': ' . $e->getMessage() . "\n";
    echo $e->getFile() . ':' . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
