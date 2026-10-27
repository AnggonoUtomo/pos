<?php

namespace App\Modules\Inventory\Items;

use App\Modules\Inventory\Items\Application\Contracts\ItemImageStore;
use App\Modules\Inventory\Items\Application\Contracts\ItemPricingLookup;
use App\Modules\Inventory\Items\Application\Contracts\ItemRepository;
use App\Modules\Inventory\Items\Application\Contracts\ItemStockLookup;
use App\Modules\Inventory\Items\Application\Contracts\ItemUnitUsage;
use App\Modules\Inventory\Items\Application\Contracts\SaleItemCatalog;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Models\ItemMedia;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Repositories\EloquentItemPricingLookup;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Repositories\EloquentItemRepository;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Repositories\EloquentItemStockLookup;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Repositories\EloquentSaleItemCatalog;
use App\Modules\Inventory\Items\Infrastructure\Persistence\Repositories\MediaLibraryItemImageStore;
use App\Modules\Inventory\Items\Infrastructure\Support\ItemImagePathGenerator;
use App\Modules\Inventory\Items\Infrastructure\Support\ItemMediaObserver;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app['config']->set('media-library.media_model', ItemMedia::class);
        $this->app['config']->set('media-library.media_observer', ItemMediaObserver::class);
        $this->app['config']->set('media-library.path_generator', ItemImagePathGenerator::class);
        $this->app->bind(ItemImageStore::class, MediaLibraryItemImageStore::class);
        $this->app->bind(SaleItemCatalog::class,
            EloquentSaleItemCatalog::class);
        $this->app->bind(ItemStockLookup::class, EloquentItemStockLookup::class);
        $this->app->bind(ItemRepository::class, EloquentItemRepository::class);
        $this->app->bind(ItemPricingLookup::class, EloquentItemPricingLookup::class);
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
