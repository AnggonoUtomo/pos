<?php

namespace App\Modules\Purchasing\Suppliers;

use App\Modules\Purchasing\Suppliers\Application\Contracts\SupplierLookup;
use App\Modules\Purchasing\Suppliers\Application\Contracts\SuppliersRepository;
use App\Modules\Purchasing\Suppliers\Infrastructure\Persistence\Repositories\EloquentSupplierLookup;
use App\Modules\Purchasing\Suppliers\Infrastructure\Persistence\Repositories\EloquentSuppliersRepository;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SuppliersRepository::class, EloquentSuppliersRepository::class);
        $this->app->bind(SupplierLookup::class, EloquentSupplierLookup::class);
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
