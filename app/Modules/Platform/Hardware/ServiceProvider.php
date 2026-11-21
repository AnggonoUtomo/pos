<?php

namespace App\Modules\Platform\Hardware;

use App\Modules\Platform\Hardware\Application\Contracts\HardwareAvailability;
use App\Modules\Platform\Hardware\Application\Contracts\HardwareRepository;
use App\Modules\Platform\Hardware\Infrastructure\Persistence\Repositories\DatabaseHardwareRepository;
use App\Modules\Platform\Hardware\Infrastructure\Support\ConfiguredHardwareAvailability;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/Config/hardware.php', 'pos_hardware');
        $this->app->bind(HardwareAvailability::class, ConfiguredHardwareAvailability::class);
        $this->app->bind(HardwareRepository::class, DatabaseHardwareRepository::class);
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
