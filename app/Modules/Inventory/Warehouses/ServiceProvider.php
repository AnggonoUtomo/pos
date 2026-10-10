<?php

namespace App\Modules\Inventory\Warehouses;

use App\Modules\Inventory\Warehouses\Application\Contracts\WarehouseRepository;
use App\Modules\Inventory\Warehouses\Infrastructure\Persistence\Repositories\EloquentWarehouseRepository;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->bind(WarehouseRepository::class, EloquentWarehouseRepository::class);
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
