<?php
/**
 * Build release.zip for Xserver upload (Windows-friendly).
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$zipPath = $root . DIRECTORY_SEPARATOR . 'release.zip';
$envSource = $root . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'PRODUCTION_ENV.secret.txt';
$envTarget = $root . DIRECTORY_SEPARATOR . '.env.production.release';

if (! is_file($envSource)) {
    fwrite(STDERR, "Missing tools/PRODUCTION_ENV.secret.txt\nRun tools/prepare_production_env.bat first.\n");
    exit(1);
}

copy($envSource, $root . DIRECTORY_SEPARATOR . '.env');
copy($envSource, $envTarget);

$token = '';
foreach (file($envSource, FILE_IGNORE_NEW_LINES) as $line) {
    $line = trim($line);
    if (str_starts_with($line, 'APP_SETUP_TOKEN=')) {
        $token = trim(substr($line, strlen('APP_SETUP_TOKEN=')), " \t\"'");
        break;
    }
}
if ($token === '') {
    fwrite(STDERR, "APP_SETUP_TOKEN missing in PRODUCTION_ENV.secret.txt\n");
    exit(1);
}
file_put_contents($root . DIRECTORY_SEPARATOR . 'unpack.token', $token . "\n");

if (is_file($zipPath)) {
    unlink($zipPath);
}

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
    fwrite(STDERR, "Cannot create release.zip\n");
    exit(1);
}

$excludeDirNames = [
    '.git' => true,
    '.github' => true,
    'node_modules' => true,
    'tests' => true,
    'deploy-ftp' => true,
    'tools' => true,
];

$excludeFiles = [
    'release.zip' => true,
    'release_via_github.bat' => true,
    'phpunit.xml' => true,
    '.env.example' => true,
    '.env.production.example' => true,
    'deploy.config.bat' => true,
    'deploy.config.example.bat' => true,
    'README.md' => true,
    'RELEASE.md' => true,
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    /** @var SplFileInfo $file */
    $full = $file->getPathname();
    $rel = substr($full, strlen($root) + 1);
    $rel = str_replace('\\', '/', $rel);

    $parts = explode('/', $rel);
    if (isset($excludeDirNames[$parts[0]])) {
        continue;
    }
    if (isset($excludeFiles[$rel])) {
        continue;
    }
    if (str_starts_with($rel, 'public/uploads/project_files/') && ! str_ends_with($rel, '.gitkeep')) {
        continue;
    }
    if (str_starts_with($rel, 'uploads/')) {
        continue;
    }
    if (str_ends_with($rel, '.sql') || str_ends_with($rel, '.md')) {
        continue;
    }
    if (preg_match('#^bootstrap/cache/(config|routes-.+|events)\\.php$#', $rel)) {
        continue;
    }
    if (! $file->isFile()) {
        continue;
    }

    $zip->addFile($full, $rel);
}

$zip->close();

echo "Created {$zipPath}\n";
echo "SETUP_TOKEN={$token}\n";
