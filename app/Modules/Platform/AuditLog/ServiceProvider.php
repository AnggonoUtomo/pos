<?php

namespace App\Modules\Platform\AuditLog;

use App\Modules\Platform\AuditLog\Application\Contracts\AuditLogReader;
use App\Modules\Platform\AuditLog\Infrastructure\Persistence\Repositories\DatabaseAuditLogReader;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuditLogReader::class,
            DatabaseAuditLogReader::class);
    }

    public function boot(): void
    {
        if (is_file(__DIR__.'/Routes/web.php')) {
            $this->loadRoutesFrom(__DIR__.'/Routes/web.php');
        }

        if (is_dir(__DIR__.'/Database/Migrations')) {
            $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        }
    }
}
