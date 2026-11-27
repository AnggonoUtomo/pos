<?php

use App\Console\Commands\MakeModuleCommand;
use App\Http\Middleware\HandleInertiaRequests;
use App\Modules\Platform\Identity\Presentation\Middleware\EnsureActiveAccount;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

$standaloneEnabled = getenv('POS_STANDALONE') === '1';
$application = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        MakeModuleCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            EnsureActiveAccount::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$application->instance('pos.standalone.enabled', $standaloneEnabled);

if ($standaloneEnabled) {
    $configuredRoot = (string) getenv('POS_DATA_ROOT');
    if (! preg_match('~^(?:[A-Za-z]:[\\\\/]|/)~', $configuredRoot) || str_starts_with($configuredRoot, '//')) {
        throw new RuntimeException('Data root standalone harus berupa path lokal absolut.');
    }
    $root = realpath($configuredRoot);
    $release = (string) getenv('POS_RELEASE_ID');
    $normalize = static fn (string $path): string => rtrim(str_replace('\\', '/', $path), '/');
    $base = $normalize(realpath(dirname(__DIR__)));
    $data = $root === false ? '' : $normalize($root);
    if ($data === '' || dirname($root) === $root || $data === $base
        || str_starts_with(strtolower($data).'/', strtolower($base).'/')
        || str_starts_with(strtolower($base).'/', strtolower($data).'/')
        || ! preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$/D', $release)) {
        throw new RuntimeException('Konfigurasi path standalone tidak valid; bootstrap dihentikan.');
    }
    foreach (['config', 'storage', 'cache/'.$release] as $directory) {
        $expected = $data.'/'.$directory;
        $actual = realpath($expected);
        if ($actual === false || ! is_dir($actual) || $normalize($actual) !== $expected) {
            throw new RuntimeException('Direktori standalone belum tersedia atau memiliki pengalihan path.');
        }
    }
    if (! is_file($data.'/config/.env') || is_link($data.'/config/.env')) {
        throw new RuntimeException('Environment privat standalone belum tersedia; tidak memakai environment toko.');
    }
    $application->useEnvironmentPath($data.'/config');
    $application->useStoragePath($data.'/storage');
    $application->addAbsoluteCachePathPrefix($data.'/');
    // Force every Laravel cache away from the release and any inherited store cache.
    foreach (['CONFIG' => 'config.php', 'ROUTES' => 'routes-v7.php', 'EVENTS' => 'events.php',
        'SERVICES' => 'services.php', 'PACKAGES' => 'packages.php'] as $kind => $filename) {
        $key = 'APP_'.$kind.'_CACHE';
        $path = $data.'/cache/'.$release.'/'.$filename;
        putenv($key.'='.$path);
        $_ENV[$key] = $_SERVER[$key] = $path;
    }
}

return $application;
