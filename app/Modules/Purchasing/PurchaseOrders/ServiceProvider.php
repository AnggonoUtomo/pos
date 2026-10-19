<?php

namespace App\Modules\Purchasing\PurchaseOrders;

use App\Modules\Purchasing\PurchaseOrders\Application\Contracts\PurchaseOrderRepository;
use App\Modules\Purchasing\PurchaseOrders\Application\Contracts\ReturnPurchaseLookup;
use App\Modules\Purchasing\PurchaseOrders\Infrastructure\Persistence\Repositories\DatabaseReturnPurchaseLookup;
use App\Modules\Purchasing\PurchaseOrders\Infrastructure\Persistence\Repositories\EloquentPurchaseOrderRepository;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PurchaseOrderRepository::class, EloquentPurchaseOrderRepository::class);
        $this->app->bind(ReturnPurchaseLookup::class, DatabaseReturnPurchaseLookup::class);
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
