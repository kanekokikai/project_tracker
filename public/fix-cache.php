<?php
/**
 * 本番の壊れた bootstrap/cache を消す（config:cache をCIで実行した副作用）。
 * /fix-cache.php?token=APP_SETUP_TOKEN
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=UTF-8');

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

$cacheDir = $root . '/bootstrap/cache';
$removed = [];

foreach (glob($cacheDir . '/*.php') ?: [] as $file) {
    $base = basename($file);
    if ($base === '.gitignore') {
        continue;
    }
    // packages.php / services.php は Composer 用なので残す
    if (in_array($base, ['packages.php', 'services.php'], true)) {
        echo "keep {$base}\n";
        continue;
    }
    if (@unlink($file)) {
        $removed[] = $base;
        echo "removed {$base}\n";
    } else {
        echo "FAILED {$base}\n";
    }
}

foreach ([
    $root . '/storage/framework/cache',
    $root . '/storage/framework/sessions',
    $root . '/storage/framework/views',
    $root . '/storage/logs',
] as $dir) {
    if (! is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    @chmod($dir, 0777);
}

echo "done removed=" . count($removed) . "\n";
echo "Open https://project.kanekokikai-app.com/ next.\n";
