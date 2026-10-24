<?php

namespace App\Modules\Platform\CompanySettings;

use App\Modules\Platform\CompanySettings\Application\Contracts\CompanyProfile;
use App\Modules\Platform\CompanySettings\Application\Contracts\CompanySettingsRepository;
use App\Modules\Platform\CompanySettings\Infrastructure\Persistence\Repositories\DatabaseCompanySettings;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CompanyProfile::class, DatabaseCompanySettings::class);
        $this->app->bind(CompanySettingsRepository::class, DatabaseCompanySettings::class);
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
