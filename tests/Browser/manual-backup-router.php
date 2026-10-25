<?php

use App\Modules\Platform\OperationalSettings\Application\Contracts\BackupExecutor;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$database = config('database.connections.sqlite.database');
if (! app()->environment('testing') || config('database.default') !== 'sqlite'
    || realpath(dirname($database)) !== realpath(storage_path('framework/testing'))
    || ! preg_match('/^mvp-browser-[a-f0-9]{16}\.sqlite$/D', basename($database))) {
    http_response_code(403);
    exit;
}
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$static = realpath(public_path(ltrim($path, '/')));
if ($static && str_starts_with($static, realpath(public_path()).DIRECTORY_SEPARATOR)
    && is_file($static) && strtolower(pathinfo($static, PATHINFO_EXTENSION)) !== 'php') {
    return false;
}
// Browser QA exercises permission/CSRF/audit/UI without dumping any business DB.
$app->instance(BackupExecutor::class, new class implements BackupExecutor
{
    public function run(): string
    {
        usleep(500000);

        return 'pos-20261025-220000-1234abcd.sql';
    }
});
$app->handleRequest(Request::capture());
