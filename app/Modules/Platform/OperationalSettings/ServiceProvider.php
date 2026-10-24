<?php

namespace App\Modules\Platform\OperationalSettings;

use App\Modules\Platform\OperationalSettings\Application\Contracts\BackupExecutor;
use App\Modules\Platform\OperationalSettings\Application\Contracts\OperationalSettingsRepository;
use App\Modules\Platform\OperationalSettings\Application\Contracts\PrintingPreferences;
use App\Modules\Platform\OperationalSettings\Infrastructure\Persistence\Repositories\DatabaseOperationalSettings;
use App\Modules\Platform\OperationalSettings\Infrastructure\Support\ArtisanBackupExecutor;
use App\Modules\Platform\OperationalSettings\Presentation\Console\Commands\RunDueBackup;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PrintingPreferences::class, DatabaseOperationalSettings::class);
        $this->app->bind(OperationalSettingsRepository::class, DatabaseOperationalSettings::class);
        $this->app->bind(BackupExecutor::class, ArtisanBackupExecutor::class);
    }

    public function boot(): void
    {
        $this->commands([RunDueBackup::class]);
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('pos:backup-due')->everyMinute()->withoutOverlapping(20);
        });
        if (is_file(__DIR__.'/Routes/web.php')) {
            $this->loadRoutesFrom(__DIR__.'/Routes/web.php');
        }

        if (is_dir(__DIR__.'/Database/Migrations')) {
            $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        }
    }
}
