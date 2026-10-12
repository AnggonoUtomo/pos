<?php

namespace App\Modules\Inventory\Items;

use App\Modules\Inventory\Items\Application\Contracts\ItemRepository;
use App\Modules\Inventory\Items\Application\Contracts\ItemStockLookup;
use App\Modules\Inventory\Items\Application\Contracts\ItemUnitUsage;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Repositories\EloquentItemRepository;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Repositories\EloquentItemStockLookup;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ItemStockLookup::class, EloquentItemStockLookup::class);
        $this->app->bind(ItemRepository::class, EloquentItemRepository::class);
        $this->app->bind(ItemUnitUsage::class, EloquentItemRepository::class);
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
